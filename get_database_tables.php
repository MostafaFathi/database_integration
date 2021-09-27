<?php

require("libs/config.php");
$databases = get_database_tables();

if ($_GET['create_timestamp_columns']){
    create_timestamp_columns();
}

if ($_GET['create_triggers']){
    create_triggers();
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Page Title</title>
</head>
<body>
<a style="margin: 10px" href="index.php">Home</a>
<a style="margin: 10px" href="get_database_tables.php?create_timestamp_columns=1">Create timestamp columns</a>
<a style="margin: 10px" href="get_database_tables.php?create_triggers=1">Create triggers</a>
<div style="margin: 10px">
    <table style="border: 1px solid gray; border-collapse: collapse">
        <thead>
        <tr>
            <td style="border: 1px solid gray;width: 150px">#</td>
            <td style="border: 1px solid gray;min-width: 200px">Table Name</td>
        </tr>
        </thead>
        <tbody>
        <?php $counter = 1;
        foreach ($databases as $key => $database) { ?>
            <?php foreach ($database as $table) { ?>
                <tr>
                    <td  style="border: 1px solid gray;"><?= $counter++ ?></td>
                    <td style="border: 1px solid gray;"><?= $table ?></td>
                </tr>
            <?php } ?>
        <?php } ?>
        </tbody>
    </table>
</div>


</body>
</html>
