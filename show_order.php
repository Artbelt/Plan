<?php /** show_order.php  файл отображает выбранную заявку в режиме просмотра*/

require_once('tools/tools.php');
require_once('settings.php');

require_once ('style/table.txt');


/** Номер заявки которую надо нарисовать */
$order_number = $_POST['order_number'];

/** Показываем номер заявки */
echo '<h3>Заявка:'.$order_number.'</h3><p>';

/** Формируем шапку таблицы для вывода заявки */
echo "<table style='border: 1px solid black; border-collapse: collapse; font-size: 14px;'>
        <tr>
            <th style=' border: 1px solid black'> Фильтр
            </th>
            <th style=' border: 1px solid black'> Количество, шт           
            </th>
            <th style=' border: 1px solid black'> Маркировка           
            </th>
            <th style=' border: 1px solid black'> Упаковка инд.           
            </th>  
            <th style=' border: 1px solid black'> Этикетка инд.           
            </th>
            <th style=' border: 1px solid black'> Упаковка групп.           
            </th>
            <th style=' border: 1px solid black'> Норма упаковки           
            </th>
            <th style=' border: 1px solid black'> Этикетка групп.           
            </th>    
            <th style=' border: 1px solid black'> Примечание           
            </th>     
            <th style=' border: 1px solid black'> Изготовлено, шт
            </th>  
            <th style=' border: 1px solid black'> Остаток, шт
            </th>                                                          
        </tr>";

/** Загружаем из БД заявку */
$result = show_order($order_number);

/** Разбор массива значений по подключению */
while ($row = $result->fetch_assoc()){
    $difference = (int)$row['count']-(int)select_produced_filters_by_order($row['filter'],$order_number)[1];
    echo "<tr style='hov'>"
        ."<td>".$row['filter']."</td>"
        ."<td>".$row['count']."</td>"
        ."<td>".$row['marking']."</td>"
        ."<td>".$row['personal_packaging']."</td>"
        ."<td>".$row['personal_label']."</td>"
        ."<td>".$row['group_packaging']."</td>"
        ."<td>".$row['packaging_rate']."</td>"
        ."<td>".$row['group_label']."</td>"
        ."<td>".$row['remark']."</td>"
        ."<td>".select_produced_filters_by_order($row['filter'],$order_number)[1]."</td>"
        ."<td>".$difference."</td>"
        ."</tr>";
}

echo "</table>";

/** Кнопка перехода в режим планирования для У2*/
echo "<br><form action='order_planning_U2.php' method='post'>"
    ."<input type='hidden' name='order_number' value='$order_number'>"
    ."<input type='submit' value='Режим простого планирования'>"
    ."</form>";

