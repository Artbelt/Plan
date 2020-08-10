<?php
$application_name = 'Система планирования производства. Easy_Plan';
/** ---------------Параметры подключения к БД-------------------- */
$mysql_host = '127.0.0.1';
$mysql_database = 'plan';
$mysql_user = 'root';
$mysql_user_pass = '';
/** -----------------Настройки раскроя рулонов------------------- */
$width_of_main_roll = 1200; /** ширина бухты, идущей в порезку */
$main_roll_length = 500; /** Длина раскраиваемой бухты */
$min_gap = 5; /** минимальный остаток в бухте */
$max_gap = 30; /** максимальный остаток  в бухте*/
$max_rolls_count = 10; /** максимальное количество полос в раскрое */