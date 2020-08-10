<?php
require_once ('tools/tools.php');
require_once ('settings.php');

class Planned_order
/** Класс реализует планирование заявки и хранение всех данных при поанировании и сохранение вего в БД
 * - хранение изначальной заявки
 * - создание раскроев бухт
 * - хранение раскроев бухт-------------?
 * - хранение плана гофрирования--------?
 * -хранение плана сборки---------------?
 * -Расчет комплектующих:
 *      каркасов;
 *      предфильтров;
 * ...
*/
{
    /** СВОЙСТВА КЛАССА */

    /** переменная хранит заявку
     *  $initial_order = {filter, count, ppHeight, ppWidth, ppPleats_count, count_of_pp_per_roll} */
   private $initial_order = array();
    /** переменная хранит номер заявки */
   public $order_name;
   /** обработанный массив для раскроя */
    private $cut_array = array();
    private $test_cut_array = array();
    /** массив собраных бухт */
    private $completed_rolls = array();
    /** маркер выполнения раскроя */
    private $cut_marker = false;
    /** массив не вошедших в первичный раскрой рулонов */
    private $not_cutted_rolls = array();


   /** МЕТОДЫ КЛАССА*/

    /** сохраняем в объект заявку
     * @param $order_array
     */
   public function set_order($order_array){
        $this->initial_order = $order_array;
   }

    /** задаем имя заявки
     * @param $name
     */
    public function set_name($name){
        $this->order_name = $name;
    }

    /** проверяем все ли фильтры из заявки ест у нас в БД и возвращает названия фильтров, которых нет  в БД
     * и рисует список отстутствующих фильтров
     */
    public function check_for_new_filters() {
        $order = $this->initial_order;
        /** @var  $not_excist_filters  - массив фильтров, отсутствующих в БД*/
        $not_excist_filters = array();
        /** проходим по каждой позиции в заявке */
        for($x = 0; $x < count($order); $x++ ){
            /** проверяем есть ли фильтр в БД и если нет, то добавляем его в массив с отсутствующими фильтрами */
            if (check_filter($order[$x][0]) != true){
                array_push($not_excist_filters, $order[$x][0]);
            }
        }
        /** блок отрисовки списка  */

        for ($x = 0; $x < count($not_excist_filters); $x++){
            echo "<p> В БД ОТСУТСТВУЕТ ФИЛЬТР:<br>";
            echo '<form action="add_panel_filter_into_db.php" method="post" target="_blank">';
            echo '<input type="hidden" name="workshop" value="U2">';
            echo '<input type="hidden" name="filter_name" value="'.$not_excist_filters[$x].'">';
            echo $not_excist_filters[$x]."==>";
            load_filters_into_select();
            echo '<input type="submit" value="Добавить в БД"><br>';
            echo '</form>';
        }

        return $not_excist_filters;
    }

    /** получаем параметры гофропакетов
     * @param $roll_length
     */
    public function get_data_for_cutting($roll_length){
        /** в цикле перебираем массив заявки, извлекаем номер фильтра, по номеру делаем выборку и получаем параметры г/пакета */
        for ($x = 0; $x < count($this->initial_order); $x++){
            /** определяем имя фильтра в данной записи */
            $pp_name = 'гофропакет '.$this->initial_order[$x][0];
            /** делаем запрос к БД на выборку параметров г/пакетов */
            $result = mysql_execute("SELECT * FROM paper_package_panel WHERE p_p_name = '$pp_name'");
            if ($result->num_rows === 0) {
                echo "Данные для расчета заявки не полные. Расчет остановлен.
                       Не полные данные на фильтр $pp_name";
                exit();
            }
            $row = $result->fetch_assoc();
            /** получаем высоту */
            $pp_height = $row['p_p_height'];
            /** получаем ширину */
            $pp_width = $row['p_p_width'];
            /** получаем количество ребер */
            $pp_pleats_count = $row['p_p_pleats_count'];
            /** вычисляем количество г/пакетов с рулона */
            $count_of_pp_per_roll = round($roll_length / ((($pp_height*2+2)*$pp_pleats_count) / 1000));
            /** вычисляем необходимое количество рулонов */
            $required_number_of_rolls = round($this->initial_order[$x][1] / $count_of_pp_per_roll);

            /** добавлем в массив заявки высоту г/пакета */
            array_push($this->initial_order[$x], $pp_height);
            /** добавлем в массив заявки ширину г/пакета */
            array_push($this->initial_order[$x], $pp_width);
            /** добавлем в массив заявки количество ребер г/пакета */
            array_push($this->initial_order[$x], $pp_pleats_count);
            /** Добавляем в массив заявки количество гофропакетов с рулона */
            array_push($this->initial_order[$x], $count_of_pp_per_roll);
            /** ДОбавляяем в массив количество рулонов необходимое */
            array_push($this->initial_order[$x], $required_number_of_rolls);
        }
    }

    /** Инициируем массив для формирования раскроев
     * конструкция массива $cut_array{filter, pp_height, pp_width} */
    public function cut_array_init(){
        for ($x = 0; $x < count($this->initial_order); $x++){
            $temp = array();
            array_push($temp, $this->initial_order[$x][0]);
          array_push($temp, $this->initial_order[$x][2]);
          array_push($temp, $this->initial_order[$x][3]);
          $repeat_times = $this->initial_order[$x][6];
          $y=0;
          do {
              array_push($this->cut_array,  $temp);
              $y++;
          }while($y < $repeat_times);
        }
    }

    /** Перемешиваем cut-массив внутри блоков с одинаковой высотой */
    public function shuffle_cut_array_with_fixed_height(){
        // создаем массивы: для каждого типа валков [24][33][40][48][60]
        $array_24 = array();
        $array_33 = array();
        $array_40 = array();
        $array_48 = array();
        $array_60 = array();
        // наполняем эти массивы
        for ($x = 0; $x < count($this->cut_array); $x++){
            if ($this->cut_array[$x][1] == '24'){ array_push($array_24, $this->cut_array[$x]);} //если валки 24 заносим элемент в массив
            if ($this->cut_array[$x][1] == '33'){ array_push($array_33, $this->cut_array[$x]);} //если валки 33 заносим элемент в массив
            if ($this->cut_array[$x][1] == '40'){ array_push($array_40, $this->cut_array[$x]);} //если валки 40 заносим элемент в массив
            if ($this->cut_array[$x][1] == '48'){ array_push($array_48, $this->cut_array[$x]);} //если валки 48 заносим элемент в массив
            if ($this->cut_array[$x][1] == '60'){ array_push($array_60, $this->cut_array[$x]);} //если валки 60 заносим элемент в массив
        }

        shuffle($array_24); //перемешиваем массив
        shuffle($array_33); //перемешиваем массив
        shuffle($array_40); //перемешиваем массив
        shuffle($array_48); //перемешиваем массив
        shuffle($array_60); //перемешиваем массив

        // сливаем перемешанные части массива в один массив
        $this->cut_array = array_merge($this->test_cut_array,$array_60,$array_48,$array_40,$array_33,$array_24);

    }

    /** отрисовка заявки */
    public function show_order(){
        echo "filter_______count_______PPheight________PPwidth________PPpleatsCount________count_from_roll________rolls_need<br>";
        for ($x = 0; $x < (count($this->initial_order)-1); $x++){
            echo $this->initial_order[$x][0]."__________";
            echo $this->initial_order[$x][1]."__________";
            echo $this->initial_order[$x][2]."__________";
            echo $this->initial_order[$x][3]."__________";
            echo $this->initial_order[$x][4]."_________________";
            echo $this->initial_order[$x][5]."_________________";
            echo $this->initial_order[$x][6]."_________________";
            echo "<br>";
        }
    }

    /** отрисовка cut-массива */
    public function show_cut_array(){
        echo "filter______PPheight________PPwidth<br>";
        for ($x = 0; $x < (count($this->cut_array)-1); $x++){
            echo $this->cut_array[$x][0]."__________";
            echo $this->cut_array[$x][1]."__________";
            echo $this->cut_array[$x][2]."__________";
            echo "<br>";
        }
    }

    /** отрисовка test_cut-массива */
    public function show_test_cut_array(){
        echo "filter______PPheight________PPwidth<br>";
        for ($x = 0; $x < (count($this->test_cut_array)-1); $x++){
            echo $this->test_cut_array[$x][0]."__________";
            echo $this->test_cut_array[$x][1]."__________";
            echo $this->test_cut_array[$x][2]."__________";
            echo "<br>";
        }
    }

    /** отрисовка собранных бухт */
    public function show_completed_rolls(){        //собираем статистику по сформированным рулонам
        $statistic_completed_rolls_count= 0;
        for ($x=0; $x < count($this->completed_rolls); $x++){
            $statistic_completed_rolls_count = $statistic_completed_rolls_count + count($this->completed_rolls[$x]);
        }
        echo "<hr>";
        echo "В раскрой было добавлено ".$statistic_completed_rolls_count." рулон(ов)<p>";

        //Ограничиваем блок для печати
        echo "<div id='order-print'>";

        //Выводим таблицы с раскроями

        //echo "<mark>Сформированы раскрои рулонов:</mark><p>";
        $roll_initial_width = 1200;                                                //поменять на $width_of_main_roll
        for($x = 0; $x < count($this->completed_rolls); $x++){
            $test = count($this->completed_rolls);

            /** Для каждой бухты: */
            $test_array = $this->completed_rolls[$x];

            /** ------------------------------- Блок отрисовки одной бухты ----------------------------------*/
            echo "<b>№".$x."</b><br>";
            echo "<table style='border-collapse: collapse' '>";
            /** Считаем остаток */
            $ostatok = 0;
            for($y = 0; $y < count($test_array); $y++){
                $ostatok += $test_array[$y][2];
            }
            $ostatok = $roll_initial_width - $ostatok;

            /** Заносим в талицу валки */
            echo "<tr>";
            echo "<td style='font-size:11pt; border: 1px solid black' colspan='".(count($test_array)+1)."'>Бухта ".$roll_initial_width." мм, бумага гладкая, остаток ".$ostatok." мм</td>";
            echo "</tr>";
            echo "<tr>";
            for($y = 0; $y < count($test_array);$y++){
                /** Высчитываем ширину рулона в масштабе 1/2 */
                $roll_size = $test_array[$y][2]/2;
                echo "<td width=".$roll_size." style='font-size:9pt; border: 1px solid black' >";
                echo $test_array[$y][0]."<br>";
                echo $test_array[$y][1]."<br>";
                echo "<b>".$test_array[$y][2]."</b> мм<br>";
                echo "</td>";
            }
            echo "<td width='.$ostatok.' style='font-size:9pt; border: 1px solid black; background-color: #ababab'> </td>";
            echo "</tr>";
            echo "</table><p>";
            /** ---------------------------------------------------------------------------------------------- */
        }

        //Ограничиваем блок для печати
        echo "</div><p>";

        echo "<button name=\"send\" onclick=\"print()\">Распечатать задание в порезку</button><p>";

    }

    /** Отображение рулонов, не попавших в раскрой  */

    function show_not_completed_rolls(){
        echo "Не вошло в раскрой ".count($this->not_cutted_rolls)." рулон(ов) <p>";
       $this->not_cutted_rolls;
       echo "<table>";
       for ($x = 0; $x < count($this->not_cutted_rolls); $x++){
           echo "<tr>";
           echo "<td>".$this->not_cutted_rolls[$x][0]."</td>";
           echo "<td>[".$this->not_cutted_rolls[$x][1]."]</td>";
           echo "<td>".$this->not_cutted_rolls[$x][2]."</td>";
           echo "</tr>";
       }
       echo "</table><hr>";
    }

    /** оформление заявки на раскрои сформированных рулонов */
    


    /** Определение рулона минимальной ширины в cut_array */
    function min_roll_search(){
        $roll = 10000;
        for ($x=0; $x < count($this->cut_array); $x++){
            if ($roll > $this->cut_array[$x][2]){
                //$temp = $this->cut_array[$x][2];
                $roll = $this->cut_array[$x][2];
            }
        }
        return $roll;
    }

    /** соритровка cut_array массива по убыванию высоты валков*/
    public function sort_cut_array(){
        usort($this->cut_array, function($a, $b){
            return ($b[1] - $a[1]);
        });
    }

    public function sort_not_completed_rolls_array (){
        usort($this->not_cutted_rolls, function($a, $b){
            return ($b[1] - $a[1]);
        });
    }

    /**
     * Алгоритм раскроя:
     * 1)из массива initial_order выбираем значения в массив cut_array
     * initial_order => cut_array { filter , pp_height , pp_width }
     * пример: initial_order{ [AF1601], 284, [48], [199], 60, 85,3} => cut_array{AF1601, 48, 199}
     *
     * 2)вместо #rolls_need добавляем необходимое количество строк в cut_array :
     * пример: initial_order{ AF1601, 284, 48, 199, 60, 85,[3]} => cut_array{AF1601, 48, 199;
     *                                                                       AF1601, 48, 199;
     *                                                                       AF1601, 48, 199}
     *
     * 3)отсортировываем массив по высоте валков (pp_height)
     *
     * 4)находим минимальной ширины рулон в cut_array
     *
     * 4)начиная с начала массива cut_array подряд добавляем рулоны в temp_roll, проверяя:
     *  4.1.не последний ли это рулон в массиве? если  последний? -------------------------------------------------->
     *      4.2. будет ли остаток рулона после добавления этой позиции больше чем минимальный ролик в массиве cut_array? Потому что если остаток
     *           будет меньше мы не сможем ни чего больше в рулон добавить.
     *           если ДА то добавляем cut_array[$x] -> temp_roll, если НЕТ, то переходим к следующему рулону. -------------------------------->
     *
     *
     *
     */

    /** Выполняем раскрой */
   public function cut_execute($width_of_main_roll, $max_gap, $min_gap){

   $count_of_completed_rolls = 0;

   /** @var  $round_complete - переменная показывает резултьативность текущего прохода по списку */


        $this->cut_marker = true;
  $counter = 0;
        /** @var  $temp_roll - временный рулон, используется в роли буфера*/
        $temp_roll = array();
        /** @var  $total_width - суммарная ширина позиций, собранных в рулон*/
  $total_width = 0;
  $safety_lock=100;
  $roll_complete = false;
        /** @var  $min_width_of_roll - минимальная используемая ширина рулона, например: 1175 = 1200 - 25(максимально-допустимый отход) */
        $min_width_of_roll = $width_of_main_roll - $max_gap;
        /** @var  $max_width_of_roll - максимальная используемая ширина рулона, например: 1195 = 1200 - 5(минимальный обрезок) */
        $max_width_of_roll = $width_of_main_roll - $min_gap;
        /** @var  $completed_rolls - массив собранных бухт */
        $this->completed_rolls = array();
        $start_cycle =0;
        $round_complete = true;


//$this->show_cut_array();

        /** Сортировка cut-массива по валкам */
       $this->sort_cut_array();

//$this->show_cut_array();

        for ($a = 0; $a < 40; $a++){

        array_splice($temp_roll,0);
        $total_width = 0;

             /** Если рулон не собрался -> смещаем начало обхода на 1 позицию */
             if ($round_complete){
                 $start_cycle =0;
                 $round_complete = false;
            } else{
                 /** перемешивание єлементов массива в случае если предыдущий проход был не результативен                   *
                 /* перемешивание массива происходит, внутри групп с одной высотой валков */
                 $this->shuffle_cut_array_with_fixed_height();
            }
                /** процедура сборки одного рулона */
                for($x = $start_cycle; $x < count($this->cut_array); $x++){
                    /** находим минимальной ширины рулон */
                    $min_roll_size = $this->min_roll_search();
                    /** находим остаток рулона после добавления текущего рулона */
                    $ostatok = $max_width_of_roll - $total_width - $this->cut_array[$x][2];
                    /** если остаток рулона после добавления этой позиции будет больше чем минимальный ролик в массиве cut_array
                     * или остаток равен минимальному размеру рулона или попадает в диапазон требуемого остатка */
                    if (($ostatok > $min_roll_size) || ($ostatok == ($min_roll_size)) || (($ostatok > 5) and ($ostatok < 30))) {
                        /** добавляем в temp_roll cut_array[$x][width] */
                        array_push($temp_roll, $this->cut_array[$x]);
                       /** увеличиваем суммарную ширину собираемой бухты */
                        $total_width = $total_width + $this->cut_array[$x][2];
                    }else{
                        /** если остаток рулона после добавления этой позиции будет меньше чем минимальный ролик в массиве -> не трогаем его и переходим дальше*/
                        continue;
                    }
                    /** если ширина рулона попадает в требуемый диапазон */
                    if (($total_width < $max_width_of_roll) and ($total_width > $min_width_of_roll)){
                        /** убираем из  cut_array позиции, которые вошли temp_rolls*/
                                for($y=0; $y < count($temp_roll);$y++){
                                    $deleted = false;
                                    for ($c = 0; $c < count($this->cut_array);$c++){
                                        if(($temp_roll[$y] == $this->cut_array[$c])&(!$deleted)){
                                            /** удаляем из cut_array запись, существующую в temp_array */
                                            array_splice($this->cut_array,$c,1);
                                            $deleted = true;
                                       }
                                    }
                                }
                        /** добавляем собранный рулон в completed_rolls */
                        array_push($this->completed_rolls, $temp_roll);


                        //$this->show_completed_rolls();
                        $count_of_completed_rolls++;
                        $this->$round_complete = true;
                    }
              //  $x++;
                }
                /** конец процедуры сборки одного рулона */
        }

        $this->not_cutted_rolls = $this->cut_array;

  }

  /** Расчет  необходимых каркасов для выполнения запявки*/
   public function component_analysis_wireframe($order_number){
        echo "Заявка на изготовление каркасов:";
        $sql = "SELECT orders.filter, panel_filter_structure.filter, orders.count".
                "FROM orders, panel_filter_structure".
                "WHERE orders.order_number='У2_22-23'".
                "AND orders.filter = panel_filter_structure.filter".
                "AND panel_filter_structure.wireframe!='';";
        $result = mysql_execute($sql);

  }

}

