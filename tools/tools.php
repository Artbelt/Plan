<?php /** tools.php в файле прописаны разные функции */

/** ПОдключаем функции */
require_once('settings.php') ;


/** Вывод массива в удобном виде
 * @param $a
 */
function print_r_my ($a){
    if (gettype($a)=='array') {
        echo "<pre>";
        print_r($a);
        echo "</pre>";
    }
}

/** Создание <SELECT> списка с перечнем заявок */
function load_orders(){

    global $mysql_host,$mysql_user,$mysql_user_pass,$mysql_database;

    /** Создаем подключение к БД */
    $mysqli = new mysqli($mysql_host,$mysql_user,$mysql_user_pass,$mysql_database);

    /** Выполняем запрос SQL для загрузки заявок*/
    $sql = "SELECT DISTINCT order_number, workshop FROM orders;";

    /** Если запрос не удачный -> exit */
    if (!$result = $mysqli->query($sql)){ echo "Ошибка: Наш запрос не удался и вот почему: \n Запрос: " . $sql . "\n"."Номер ошибки: " . $mysqli->errno . "\n Ошибка: " . $mysqli->error . "\n";
        exit;
    }

    /** Разбор массива значений  */
    echo "<select id='selected_order'>";
    while ($orders_data = $result->fetch_assoc()){
            echo "<option name='order_number' value=".$orders_data['order_number'].">".$orders_data['order_number']."</option>";
    }
    echo "</select>";

    /** Закрываем соединение */
    $result->close();
    $mysqli->close();
}

/** СОздание <SELECT> списка с перечнем фильтров имеющихся в БД */
function load_filters_into_select(){

    global $mysql_host,$mysql_user,$mysql_user_pass,$mysql_database;

    /** Создаем подключение к БД */
    $mysqli = new mysqli($mysql_host,$mysql_user,$mysql_user_pass,$mysql_database);

    /** Выполняем запрос SQL для загрузки заявок*/
    $sql = "SELECT DISTINCT filter FROM panel_filter_structure;";

    /** Если запрос не удачный -> exit */
    if (!$result = $mysqli->query($sql)){ echo "Ошибка: Наш запрос не удался и вот почему: \n Запрос: " . $sql . "\n"."Номер ошибки: " . $mysqli->errno . "\n Ошибка: " . $mysqli->error . "\n";
        exit;
    }

    /** Разбор массива значений  */
    echo "<select name='analog_filter'>";
    echo "<option value=''>выбор аналога</option>";
    while ($orders_data = $result->fetch_assoc()){
        echo "<option value=".$orders_data['filter'].">".$orders_data['filter']."</option>";
    }
    echo "</select>";

    /** Закрываем соединение */
    $result->close();
    $mysqli->close();
}


/** Списание фильтров в выпущенную продукцию
 * @param $date_of_production
 * @param $order_number
 * @param $filters
 * @return bool
 */
function write_of_filters($date_of_production, $order_number, $filters){

    global $mysql_host,$mysql_user,$mysql_user_pass,$mysql_database;

    /** Создаем подключение к БД */
    $mysqli = new mysqli($mysql_host,$mysql_user,$mysql_user_pass,$mysql_database);

    /** Цикл для разбора значений массива со значениями "фильтер - количество" */
    foreach ($filters as $filter_record) {

        /** Получили значение {елемент масива[фильтр][количество]} */
        $filter_name = $filter_record[0];
        $filter_count = $filter_record[1];

        /** Форматируем sql-запрос, "записать в БД -> дата -> заявка -> фильтер -> количство" */
        $sql = "INSERT INTO manufactured_production (date_of_production, name_of_filter, count_of_filters, name_of_order) 
                VALUES ('$date_of_production','$filter_name','$filter_count','$order_number')";

        /** Выполняем запрос. Если запрос не удачный -> exit */
        if (!$result = $mysqli->query($sql)){ echo "Ошибка: Наш запрос не удался и вот почему: \n Запрос: " . $sql . "\n"."Номер ошибки: " . $mysqli->errno . "\n Ошибка: " . $mysqli->error . "\n";
            /** в случае неудачи функция выводит FALSЕ */
            return false;
            exit;
        }
    }

    /** Закрываем соединение */
    return true;
}

/** Функция возвращает получает дату в формате dd-mm-yy а возвращает yy-mm-dd */
function reverse_date($date){

    $reverse_date=date('yy-m-d',strtotime($date));
    return $reverse_date;
}

/** Функция возвращает количество произведенных указанных фильтров по указанной заявке */
/** функция возвращает массив ARRAY['ПЕРЕЧЕНЬ_ДАТ_И_КОЛИЧЕСТВ','КОЛИЧЕСТВО_СУММАРНОЕ'] */
function select_produced_filters_by_order($filter_name, $order_name){

    global $mysql_host,$mysql_user,$mysql_user_pass,$mysql_database;
    $count = 0;
    /** Подключение к БД   */
    $mysqli = new mysqli($mysql_host,$mysql_user, $mysql_user_pass, $mysql_database);

    /** Если не получилось подключиться.  */
    if ($mysqli->connect_errno) {
        echo  "Номер ошибки: " . $mysqli->connect_errno . "\n" . "Ошибка: " . $mysqli->connect_error . "\n";
        return "ERROR#02";
    }
    /** Выполняем запрос SQL по подключению */
    $sql = "SELECT * FROM manufactured_production WHERE name_of_order = '$order_name' AND name_of_filter = '$filter_name';";

    /** Если запрос не удался */
    if (!$result = $mysqli->query($sql)) {
        echo "Номер ошибки: " . $mysqli->errno . "\n". "Ошибка: " . $mysqli->error . "\n";
        return "ERROR#01";
    }

    /** Разбираем результата запроса */
    while ($row = $result->fetch_assoc()){
        $count += $row['count_of_filters'];
    }

    /** Создаем массив для вывода результата*/
    $result_part_one = "#in_construction";
    $result_part_two = $count;
    $result_array = [];
    array_push($result_array,$result_part_one);
    array_push($result_array,$result_part_two);

    return $result_array;
}

/** Функция выполняет запрос к БД и создает выборку заявки по выбранному номеру */
function show_order($order_number){
    global $mysql_host,$mysql_user,$mysql_user_pass,$mysql_database;
    /** Подключаемся к БД для вывода заявки */
    $mysqli = new mysqli($mysql_host, $mysql_user, $mysql_user_pass, $mysql_database);
    if ($mysqli->connect_errno) {
        /** Если не получилось подключиться */
        echo 'Возникла проблема на сайте'
            . "Номер ошибки: " . $mysqli->connect_errno . "\n"
            . "Ошибка: " . $mysqli->connect_error . "\n";
        exit;
    }
    /** Выполняем запрос SQL */
    $sql = "SELECT * FROM orders WHERE order_number = '$order_number';";
    if (!$result = $mysqli->query($sql)) {
        echo "Ошибка: Наш запрос не удался и вот почему: \n"
            . "Запрос: " . $sql . "\n"
            . "Номер ошибки: " . $mysqli->errno . "\n"
            . "Ошибка: " . $mysqli->error . "\n";
        exit;
    }
    return $result; // Выход из функции, дальше какая-то лажа

//************************************************** надо разобраться, тут какая-то лажа получилась*********************************//
    /** Формируем шапку таблицы для вывода заявки */
    echo "<table style='border: 1px solid black; border-collapse: collapse; font-size: 14px;'>
        <tr>
            <th style=' border: 1px solid black'> Фильтр
            </th>
            <th style=' border: 1px solid black'> Количество, шт           
            </th>                                                     
        </tr>";

    /**  массив для сохранения заявки в объект планирования */
    $order_array = array();

    /** Разбор массива значений по подключению */
    while ($row = $result->fetch_assoc()){

        echo "<tr>"
            ."<td style=' border: 1px solid black'>".$row['filter']."</td>"
            ."<td style=' border: 1px solid black'>".$row['count']."</td>"
            ."</tr>";

        /** наполняем массив для сохранения заявки */
        $temp_array = array();
        array_push($temp_array, $row['filter']);
        array_push($temp_array, $row['count']);
        array_push($order_array,$temp_array);
    }

    echo "</table>";
//************************************************** конец лажи где надо разобраться********************************************//

}

/** Функция складывает одинаковые элементы массива. ПРимер
 *      [[1][100]    =>  [[1][140]
 *       [2][ 50]         [2][100]
 *       [2][ 50]         [3][ 10]]
 *       [1][ 40]
 *       [3][ 10]]
 */
function summ_the_same_elements_of_array($input_array){

    function compare($a, $b){ // функция для сортировки массива в usort
        if ($a == $b){
            return 0;
        }
        return ($a < $b) ? -1 : 1;
    }

    usort($input_array,"compare");


    $finish_array = array();
    $summ = 0;
    $x = 0;
    $b = 0;
    $c = 0;
    $size = count($input_array) - 1;
    for ($n = 0; $n <= $size; $n++) {

        if ($n == $size) {
            $a = $input_array[$size][0];
            $summ += $input_array[$n][1];
            $x++;
            array_push($finish_array, array($a,$summ));
            $summ = 0;
        } else {
            $a = $input_array[$n][0];
            $b = $input_array[$n + 1][0];
            $c = (int)$b - (int)$a;

            switch ($c) {
                case 0:
                    $summ += $input_array[$n][1];
                    break;
                case ($c != 0):
                    $summ += $input_array[$n][1];
                    $x++;
                    array_push($finish_array, array($a,$summ));
                    $summ = 0;
                    break;
            }
        }
    }
    return $finish_array;
}


/** Функция из заявки возвращает массив вида ...[[filter][count]][[filter][count]] */
function get_order($order_number){

    global $mysql_host,$mysql_user,$mysql_user_pass,$mysql_database;
    /** Подключаемся к БД для вывода заявки */
    $mysqli = new mysqli($mysql_host, $mysql_user, $mysql_user_pass, $mysql_database);
    if ($mysqli->connect_errno) {
        /** Если не получилось подключиться */
        echo 'Возникла проблема на сайте'
            . "Номер ошибки: " . $mysqli->connect_errno . "\n"
            . "Ошибка: " . $mysqli->connect_error . "\n";
        exit;
    }
    /** Выполняем запрос SQL */
    $sql = "SELECT * FROM orders WHERE order_number = '$order_number';";
    if (!$result = $mysqli->query($sql)) {
        echo "Ошибка: Наш запрос не удался и вот почему: \n"
            . "Запрос: " . $sql . "\n"
            . "Номер ошибки: " . $mysqli->errno . "\n"
            . "Ошибка: " . $mysqli->error . "\n";
        exit;
    }

    /**  массив для сохранения заявки в объект планирования */
    $order_array = array();

    /** Разбор массива значений по подключению */
    while ($row = $result->fetch_assoc()){

        /** наполняем массив для сохранения заявки */
        $temp_array = array();
        array_push($temp_array, $row['filter']);
        array_push($temp_array, $row['count']);
        array_push($order_array,$temp_array);
    }
    return $order_array;
}

/** Функция обеспечивает подключение к БД и выполняет запрос sql */
/** возвращает результат выполнения sql запроса */
function mysql_execute($sql){
    global $mysql_host,$mysql_user,$mysql_user_pass,$mysql_database;
    /** Подключаемся к БД */
    $mysqli = new mysqli($mysql_host, $mysql_user, $mysql_user_pass, $mysql_database);
    if ($mysqli->connect_errno) {
        /** Если не получилось подключиться */
        echo 'Возникла проблема на сайте'
            . "Номер ошибки: " . $mysqli->connect_errno . "\n"
            . "Ошибка: " . $mysqli->connect_error . "\n";
        exit;
    }
    /** Выполняем запрос SQL */
    if (!$result = $mysqli->query($sql)) {
        echo "Ошибка: Наш запрос не удался и вот почему: \n"
            . "Запрос: " . $sql . "\n"
            . "Номер ошибки: " . $mysqli->errno . "\n"
            . "Ошибка: " . $mysqli->error . "\n";
        exit;
    }

    return $result;
}

/** Функция формирует список коробок имеющихся в БД
 * если в функцию передается переменная, то выбирается коробка, соответствующая переменной
 */
function select_boxes($index){
    global $mysql_host,$mysql_user,$mysql_user_pass,$mysql_database;
    $mysqli = new mysqli($mysql_host,$mysql_user,$mysql_user_pass,$mysql_database);
    /** Подключаемся к БД */
    if ($mysqli->connect_errno){/** Если не получилось подключиться */
        echo 'Возникла проблема на сайте'."Номер ошибки: " . $mysqli->connect_errno . "\n"."Ошибка: " . $mysqli->connect_error . "\n";
        exit;
    }

    $sql = "SELECT * FROM box";
    /** Выполняем запрос SQL */
    if (!$result = $mysqli->query($sql)) {
        echo "Ошибка: Наш запрос не удался и вот почему: \n"
            . "Запрос: " . $sql . "\n"
            . "Номер ошибки: " . $mysqli->errno . "\n"
            . "Ошибка: " . $mysqli->error . "\n";
        exit;
    }
    /** извлечение ассоциативного массива */

    echo "<option></option>";

    while ($row = $result->fetch_assoc()) {
        echo "<option";
        // если номер коробки указан, то делаем ее выбранной
        if ($row['b_name'] == $index) echo " selected ";
        echo ">".$row['b_name']."</option>";
    }

    /* удаление выборки */
    $result->free();

}

/** Функция формирует список ящиков имеющихся в БД */
function select_g_boxes($index){

    global $mysql_host,$mysql_user,$mysql_user_pass,$mysql_database;
    $mysqli = new mysqli($mysql_host,$mysql_user,$mysql_user_pass,$mysql_database);
    /** Подключаемся к БД */
    if ($mysqli->connect_errno){/** Если не получилось подключиться */
        echo 'Возникла проблема на сайте'."Номер ошибки: " . $mysqli->connect_errno . "\n"."Ошибка: " . $mysqli->connect_error . "\n";
        exit;
    }

    $sql = "SELECT * FROM g_box";
    /** Выполняем запрос SQL */
    if (!$result = $mysqli->query($sql)) {
        echo "Ошибка: Наш запрос не удался и вот почему: \n"
            . "Запрос: " . $sql . "\n"
            . "Номер ошибки: " . $mysqli->errno . "\n"
            . "Ошибка: " . $mysqli->error . "\n";
        exit;
    }
    /** извлечение ассоциативного массива */
    echo "<option></option>";


     while ($row = $result->fetch_assoc()) {
        echo "<option";
        // если номер ящика указан, то делаем ее выбранной
        if ($row['gb_name'] == $index) echo " selected ";
        echo ">".$row['gb_name']."</option>";
    }



    /* удаление выборки */
    $result->free();

}

/** Проверка наличия фильтра в БД */
function check_filter($filter){

    $result = mysql_execute("SELECT * FROM panel_filter_structure WHERE filter = '$filter'");

    if ($result->num_rows > 0) {
        $a = true;
    } else {
        $a = false;
    }

    return $a;
}

/** Получаем всю информацию о фильтре:
 * -------------------------------
 * гофропакет: длина, ширина, высота, Количество ребер, усилитель, поставщик, комментарий
 * каркас: длина, ширина, материал, поставщик
 * предфильтр: длина, ширина, материал, поставщик, комментарий
 * индивидуальная упаковка
 * групповая упаковка
 * примечание
 * ----------------------------
 *
 */
function get_filter_data($target_filter){

    global $mysql_host,$mysql_user,$mysql_user_pass,$mysql_database;
    /** Создаем подключение к БД */
    $mysqli = new mysqli($mysql_host,$mysql_user,$mysql_user_pass,$mysql_database);

    /** @var  $result_array  массив вывода результата*/
    $result_array = array();

    /** ГОФРОПАКЕТ */
    $result_array['paper_package_name'] = 'гофропакет '.$target_filter;

    /** Выполняем запрос SQL */
    $sql = "SELECT * FROM paper_package_panel WHERE p_p_name = '".$result_array['paper_package_name']."';";
    /** Если запрос не удачный -> exit */
    if (!$result = $mysqli->query($sql)){ echo "Ошибка: Наш запрос не удался и вот почему: \n Запрос: " . $sql . "\n"."Номер ошибки: " . $mysqli->errno . "\n Ошибка: " . $mysqli->error . "\n"; exit; }
    /** Разбор массива значений  */
    $paper_package_data = $result->fetch_assoc();

    $result_array['paper_package_length'] = $paper_package_data['p_p_length'];
    $result_array['paper_package_width'] = $paper_package_data['p_p_width'];
    $result_array['paper_package_height'] = $paper_package_data['p_p_height'];
    $result_array['paper_package_pleats_count'] = $paper_package_data['p_p_pleats_count'];
    $result_array['paper_package_amplifier'] = $paper_package_data['p_p_amplifier'];
    $result_array['paper_package_supplier'] = $paper_package_data['supplier'];
    $result_array['paper_package_remark'] = $paper_package_data['p_p_remark'];

    /** КАРКАС */
    $result_array['wireframe_name'] = 'каркас '.$target_filter;

    /** Выполняем запрос SQL */
    $sql = "SELECT * FROM wireframe_panel WHERE w_name = '".$result_array['wireframe_name']."';";
    /** Если запрос не удачный -> exit */
    if (!$result = $mysqli->query($sql)){ echo "Ошибка: Наш запрос не удался и вот почему: \n Запрос: " . $sql . "\n"."Номер ошибки: " . $mysqli->errno . "\n Ошибка: " . $mysqli->error . "\n"; exit; }
    /** Разбор массива значений  */
    $wireframe_data = $result->fetch_assoc();

    if ($wireframe_data == null) {
        $result_array['wireframe_name']=null;
        $result_array['wireframe_length'] = null;
        $result_array['wireframe_width'] = null;
        $result_array['wireframe_material'] = null;
        $result_array['wireframe_supplier'] = null;
    } else {
        $result_array['wireframe_length'] = $wireframe_data['w_length'];
        $result_array['wireframe_width'] = $wireframe_data['w_width'];
        $result_array['wireframe_material'] = $wireframe_data['w_material'];
        $result_array['wireframe_supplier'] = $wireframe_data['w_supplier'];
    }

    /** ПРЕДФИЛЬТР */
    $result_array['prefilter_name'] = 'предфильтр '.$target_filter;

    /** Выполняем запрос SQL */
    $sql = "SELECT * FROM prefilter_panel WHERE p_name = '".$result_array['prefilter_name']."';";
    /** Если запрос не удачный -> exit */
    if (!$result = $mysqli->query($sql)){ echo "Ошибка: Наш запрос не удался и вот почему: \n Запрос: " . $sql . "\n"."Номер ошибки: " . $mysqli->errno . "\n Ошибка: " . $mysqli->error . "\n"; exit; }
    /** Разбор массива значений  */
    $prefilter_data = $result->fetch_assoc();

    if ($prefilter_data == null){
        $result_array['prefilter_name'] = null;
        $result_array['prefilter_length'] = null;
        $result_array['prefilter_width'] = null;
        $result_array['prefilter_material'] = null;
        $result_array['prefilter_supplier'] = null;
        $result_array['prefilter_remark'] = null;
    } else {
        $result_array['prefilter_length'] =  $prefilter_data['p_length'];
        $result_array['prefilter_width'] = $prefilter_data['p_width'];
        $result_array['prefilter_material'] = $prefilter_data['p_material'];
        $result_array['prefilter_supplier'] = $prefilter_data['p_supplier'];
        $result_array['prefilter_remark'] = $prefilter_data['p_remark'];
    }

    /** КОРОБКА ИНДИВИДУАЛЬНАЯ */
    /** Выполняем запрос SQL */
    $sql = "SELECT box FROM panel_filter_structure WHERE filter = '".$target_filter."';";
    /** Если запрос не удачный -> exit */
    if (!$result = $mysqli->query($sql)){ echo "Ошибка: Наш запрос не удался и вот почему: \n Запрос: " . $sql . "\n"."Номер ошибки: " . $mysqli->errno . "\n Ошибка: " . $mysqli->error . "\n"; exit; }
    /** Разбор массива значений  */
    $box_data = $result->fetch_assoc();
    $result_array['box'] = $box_data['box'];

    /** КОРОБКА ГРУППОВАЯ */
    /** Выполняем запрос SQL */
    $sql = "SELECT g_box FROM panel_filter_structure WHERE filter = '".$target_filter."';";
    /** Если запрос не удачный -> exit */
    if (!$result = $mysqli->query($sql)){ echo "Ошибка: Наш запрос не удался и вот почему: \n Запрос: " . $sql . "\n"."Номер ошибки: " . $mysqli->errno . "\n Ошибка: " . $mysqli->error . "\n"; exit; }
    /** Разбор массива значений  */
    $g_box_data = $result->fetch_assoc();
    $result_array['g_box'] = $g_box_data['g_box'];

    /** ПРИМЕЧАНИЯ */
    /** Выполняем запрос SQL */
    $sql = "SELECT comment FROM panel_filter_structure WHERE filter = '".$target_filter."';";
    /** Если запрос не удачный -> exit */
    if (!$result = $mysqli->query($sql)){ echo "Ошибка: Наш запрос не удался и вот почему: \n Запрос: " . $sql . "\n"."Номер ошибки: " . $mysqli->errno . "\n Ошибка: " . $mysqli->error . "\n"; exit; }
    /** Разбор массива значений  */
    $comment_data = $result->fetch_assoc();
    $result_array['comment'] = $comment_data['comment'];

    /** Закрываем соединение */
    $result->close();
    $mysqli->close();

    return $result_array;
}

/** Расчет  необходимого количества каркасов для выполнения запявки*/
function component_analysis_wireframe($order_number){

    // шапка таблицы
    echo '<table style=" border-collapse: collapse;">';
    echo '<tr><td colspan="4"><h3 style="font-family: Calibri; size: 20px;text-align: center">Заявка</h3></td></tr>';
    echo '<tr><td colspan="4">на поставку комплектующих для: У2</td></tr>';
    echo '<tr><td colspan="4"><pre> </pre></td></tr>';
    echo '<tr><td>№п/п</td><td>Комплектующее</td><td>Кол-во</td><td>Дата поставки</td></tr>';

    // запрос для выборки необходимых каркасов для выполнения заявки
    $sql = "SELECT orders.filter, panel_filter_structure.wireframe, panel_filter_structure.filter, orders.count ".
        "FROM orders, panel_filter_structure ".
        "WHERE orders.order_number='$order_number' ".
        "AND orders.filter = panel_filter_structure.filter ".
        "AND panel_filter_structure.wireframe!='';";

    $result = mysql_execute($sql);

    $i=1;// счетчик циклов для отображения в таблице порядкового номера
    foreach ($result as $value){

              echo '<tr><td>'.$i.'</td><td>'.$value['wireframe'].'</td><td>'.$value['count'].'</td><td><input type="text"></td>';
              $i++;
    }

    echo '<tr><td colspan="4"><pre> </pre></td></tr>';
    echo '<tr><td colspan="2">Дата составления заявки:</td><td colspan="2">'.date('d.m.y ').'</td></tr>';
    echo '<tr><td colspan="4"><pre> </pre></td></tr>';
    echo '<tr><td colspan="2">Заявку составил:</td><td colspan="2"><input type="text"></td></tr>';
    echo '</table>';
}

/** Расчет  необходимого количества предфильтров для выполнения запявки*/
function component_analysis_prefilter($order_number){

    // шапка таблицы
    echo '<table style=" border-collapse: collapse;">';
    echo '<tr><td colspan="4"><h3 style="font-family: Calibri; size: 20px;text-align: center">Заявка</h3></td></tr>';
    echo '<tr><td colspan="4">на поставку комплектующих для: У2</td></tr>';
    echo '<tr><td colspan="4"><pre> </pre></td></tr>';
    echo '<tr><td>№п/п</td><td>Комплектующее</td><td>Кол-во</td><td>Дата поставки</td></tr>';

    // запрос для выборки необходимых каркасов для выполнения заявки
    $sql = "SELECT orders.filter, panel_filter_structure.prefilter, panel_filter_structure.filter, orders.count ".
        "FROM orders, panel_filter_structure ".
        "WHERE orders.order_number='$order_number' ".
        "AND orders.filter = panel_filter_structure.filter ".
        "AND panel_filter_structure.prefilter!='';";

    $result = mysql_execute($sql);

    $i=1;// счетчик циклов для отображения в таблице порядкового номера
    foreach ($result as $value){

        echo '<tr><td>'.$i.'</td><td>'.$value['prefilter'].'</td><td>'.$value['count'].'</td><td><input type="text"></td>';
        $i++;
    }

    echo '<tr><td colspan="4"><pre> </pre></td></tr>';
    echo '<tr><td colspan="2">Дата составления заявки:</td><td colspan="2">'.date('d.m.y ').'</td></tr>';
    echo '<tr><td colspan="4"><pre> </pre></td></tr>';
    echo '<tr><td colspan="2">Заявку составил:</td><td colspan="2"><input type="text"></td></tr>';
    echo '</table>';
}

/** Расчет  необходимого количества гофропакетов для выполнения запявки*/
function component_analysis_paper_package($order_number){

    // шапка таблицы
    echo '<table style=" border-collapse: collapse;">';
    echo '<tr><td colspan="4"><h3 style="font-family: Calibri; size: 20px;text-align: center">Заявка</h3></td></tr>';
    echo '<tr><td colspan="4">на поставку комплектующих для: У2</td></tr>';
    echo '<tr><td colspan="4"><pre> </pre></td></tr>';
    echo '<tr><td>№п/п</td><td>Комплектующее</td><td>Кол-во</td><td>Дата поставки</td></tr>';

    // запрос для выборки необходимых каркасов для выполнения заявки
    $sql = "SELECT orders.filter, panel_filter_structure.paper_package, panel_filter_structure.filter, orders.count ".
        "FROM orders, panel_filter_structure ".
        "WHERE orders.order_number='$order_number' ".
        "AND orders.filter = panel_filter_structure.filter ".
        "AND panel_filter_structure.paper_package!='';";

    $result = mysql_execute($sql);

    $i=1;// счетчик циклов для отображения в таблице порядкового номера
    foreach ($result as $value){

        echo '<tr><td>'.$i.'</td><td>'.$value['paper_package'].'</td><td>'.$value['count'].'</td><td><input type="text"></td>';
        $i++;
    }

    echo '<tr><td colspan="4"><pre> </pre></td></tr>';
    echo '<tr><td colspan="2">Дата составления заявки:</td><td colspan="2">'.date('d.m.y ').'</td></tr>';
    echo '<tr><td colspan="4"><pre> </pre></td></tr>';
    echo '<tr><td colspan="2">Заявку составил:</td><td colspan="2"><input type="text"></td></tr>';
    echo '</table>';
}

/** Расчет  необходимого количества групповых ящиков для выполнения заявки*/
function component_analysis_group_box($order_number){

    // запрос для выборки необходимых каркасов для выполнения заявки
    $sql = "SELECT orders.filter, panel_filter_structure.paper_package, panel_filter_structure.g_box, orders.count ".
        "FROM orders, panel_filter_structure ".
        "WHERE orders.order_number='$order_number' ".
        "AND orders.filter = panel_filter_structure.filter ".
        "AND panel_filter_structure.g_box!='';";

    $result = mysql_execute($sql);
    $temp_array = array(); // массив для сложения одинковых элементов

    foreach ($result as $value){
        array_push($temp_array,array($value['g_box'],$value['count']));
    }

     $temp_array = summ_the_same_elements_of_array($temp_array);

    echo '<table style=" border-collapse: collapse;">';
    echo '<tr><td colspan="4"><h3 style="font-family: Calibri; size: 20px;text-align: center">Заявка</h3></td></tr>';
    echo '<tr><td colspan="4">на поставку ящиков груповых для: У2</td></tr>';
    echo '<tr><td colspan="4"><pre> </pre></td></tr>';
    echo '<tr><td>№п/п</td><td>Комплектующее</td><td>Кол-во</td><td>Дата поставки</td></tr>';

    $i=1;// счетчик циклов для отображения в таблице порядкового номера
    foreach ($temp_array as $value){
        echo '<tr><td>'.$i.'</td><td>'.$value[0].'</td><td>'.round(($value[1]/10)).'</td><td><input type="text"></td>';
        $i++;
    }

    echo '<tr><td colspan="4"><pre> </pre></td></tr>';
    echo '<tr><td colspan="2">Дата составления заявки:</td><td colspan="2">'.date('d.m.y ').'</td></tr>';
    echo '<tr><td colspan="4"><pre> </pre></td></tr>';
    echo '<tr><td colspan="2">Заявку составил:</td><td colspan="2"><input type="text"></td></tr>';
    echo '</table>';

}


/** Расчет  необходимого количества коробок индивидуальных для выполнения заявки*/
function component_analysis_box($order_number){

    // запрос для выборки необходимых каркасов для выполнения заявки
    $sql = "SELECT orders.filter, panel_filter_structure.paper_package, panel_filter_structure.box, orders.count ".
        "FROM orders, panel_filter_structure ".
        "WHERE orders.order_number='$order_number' ".
        "AND orders.filter = panel_filter_structure.filter ".
        "AND panel_filter_structure.box!='';";

    $result = mysql_execute($sql);
    $temp_array = array(); // массив для сложения одинковых элементов

    foreach ($result as $value){
        array_push($temp_array,array($value['box'],$value['count']));
    }

    $temp_array = summ_the_same_elements_of_array($temp_array);
    echo '<table style=" border-collapse: collapse;">';
    echo '<tr><td colspan="4"><h3 style="font-family: Calibri; size: 20px;text-align: center">Заявка</h3></td></tr>';
    echo '<tr><td colspan="4">на поставку коробок индивидуальных для: У2</td></tr>';
    echo '<tr><td colspan="4"><pre> </pre></td></tr>';
    echo '<tr><td>№п/п</td><td>Комплектующее</td><td>Кол-во</td><td>Дата поставки</td></tr>';

    $i=1;// счетчик циклов для отображения в таблице порядкового номера
    foreach ($temp_array as $value){
        echo '<tr><td>'.$i.'</td><td>'.$value[0].'</td><td>'.$value[1].'</td><td><input type="text"></td>';
        $i++;
    }

    echo '<tr><td colspan="4"><pre> </pre></td></tr>';
    echo '<tr><td colspan="2">Дата составления заявки:</td><td colspan="2">'.date('d.m.y ').'</td></tr>';
    echo '<tr><td colspan="4"><pre> </pre></td></tr>';
    echo '<tr><td colspan="2">Заявку составил:</td><td colspan="2"><input type="text"></td></tr>';
    echo '</table>';
}

?>
