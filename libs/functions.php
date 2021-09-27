<?php

function get_database_tables()
{
    global $DB;

    $rs = array();
    $sql = "SHOW TABLES;";

    try {
        $stmt = $DB->prepare($sql);

        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        echo errorMessage($ex->getMessage());
    }

    return $results;

}

function create_timestamp_columns()
{
    global $DB;

    $databases = get_database_tables();
    foreach ($databases as $key => $database) {
        foreach ($database as $table) {
            $stmt = $DB->prepare('SHOW COLUMNS FROM ' . $table . ' LIKE "SYNC_TIMESTAMP"');
            $stmt->execute();
            if ($sync_timestamp = count($stmt->fetchAll()) == 0) {
                $stmt = $DB->prepare('ALTER TABLE ' . $table . ' ADD COLUMN SYNC_TIMESTAMP TIMESTAMP ;');
                $stmt->execute();
            }

            $stmt = $DB->prepare('SHOW COLUMNS FROM ' . $table . ' LIKE "LAST_OPERATION"');
            $stmt->execute();
            if ($last_operation = count($stmt->fetchAll()) == 0) {
                $stmt = $DB->prepare('ALTER TABLE ' . $table . ' ADD COLUMN LAST_OPERATION varchar(2);');
                $stmt->execute();
            }
        }
    }
    return 'success';
}

function create_triggers()
{
    global $DB;

    $databases = get_database_tables();
    foreach ($databases as $key => $database) {
        foreach ($database as $table) {
            $stmt = $DB->prepare("DROP TRIGGER IF EXISTS before_" . $table . "_insert ");
            $stmt->execute();

            $stmt = $DB->prepare("DROP TRIGGER IF EXISTS after_" . $table . "_update ");
            $stmt->execute();

            $stmt = $DB->prepare("DROP TRIGGER IF EXISTS after_" . $table . "_delete ");
            $stmt->execute();


            $insert_trigger = "
            CREATE TRIGGER before_" . $table . "_insert 
    BEFORE INSERT ON " . $table . "
    FOR EACH ROW 
    BEGIN
         SET new.SYNC_TIMESTAMP=NOW(),  new.LAST_OPERATION='I';
    END";
            $stmt = $DB->prepare($insert_trigger);
            $stmt->execute();


            $update_trigger = "
            CREATE TRIGGER after_" . $table . "_update 
    BEFORE UPDATE ON " . $table . "
    FOR EACH ROW 
    BEGIN
         SET new.SYNC_TIMESTAMP=NOW(),  new.LAST_OPERATION='U';
    END";
            $stmt = $DB->prepare($update_trigger);
            $stmt->execute();
            $stmt = $DB->prepare('SHOW COLUMNS FROM ' . $table . ' LIKE "deleted_at"');
            $stmt->execute();
            if ($deleted_at = count($stmt->fetchAll()) > 0) {
                $delete_trigger = "
            CREATE TRIGGER after_" . $table . "_delete 
    BEFORE UPDATE ON " . $table . "
    FOR EACH ROW 
    BEGIN
    IF OLD.deleted_at != NOW() AND   new.deleted_at is not null THEN
         SET new.SYNC_TIMESTAMP=NOW(),  new.LAST_OPERATION='D';
         END IF;
    END";
                $stmt = $DB->prepare($delete_trigger);
                $stmt->execute();
            }


        }
    }
    return 'success';
}

function fetch_occurences($per_page = 20)
{
    global $DB;

    $sql = "SELECT sci_name,id,occurrence,results_json,plant_id FROM plants_occurrence WHERE processed = 0 Limit :limit ";

    try {
        $stmt = $DB->prepare($sql);
        $stmt->bindValue(':limit', (int)$per_page, PDO::PARAM_INT);

        $stmt->execute();
        $results = $stmt->fetchAll();
    } catch (Exception $ex) {
        echo errorMessage($ex->getMessage());
    }

//    if (count($results) > 0) {
//        $rs =  $results[0];
//    }

    return $results;

}

function getPlantName($id)
{
    global $DB;

    $sql = "SELECT id,name_ar,name_en FROM plants WHERE id = :id ";

    try {
        $stmt = $DB->prepare($sql);
        $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);

        $stmt->execute();
        $results = $stmt->fetchAll();
    } catch (Exception $ex) {
        echo errorMessage($ex->getMessage());
    }

    if (count($results) > 0) {
        return $results[0];
    }

    return false;

}


function get_plants($page = 0, $perpage = 20)
{
    global $DB;
    $offset = $page * $perpage;
    $rs = array();
    $sql = "SELECT * FROM plants_temp WHERE processed = 0 AND  marked = 1 Limit :limit offset :offset";

    try {
        $stmt = $DB->prepare($sql);
        $stmt->bindValue(':limit', (int)$perpage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

        $stmt->execute();
        $results = $stmt->fetchAll();
    } catch (Exception $ex) {
        echo errorMessage($ex->getMessage());
    }


    return $results;

}

function get_file_image($url, $fullpath, $extension, $plant_id = null)
{
    $max_length = 8 * 1024 * 1024;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $data = curl_exec($ch);
    curl_close($ch);
    if ($data === false) {
        echo 'cURL failed';

    }

    $contentLength = 'unknown';
    if (preg_match('/Content-Length: (\d+)/', $data, $matches)) {
        $contentLength = (int)$matches[1];
    }
    echo "Content-Length: $contentLength\n";

    if ($contentLength > $max_length) {
        echo "File is too large\n";
    } else {
        if ($contentLength !== 0) {
            //&& $contentLength !== 'unknown'
            try {
                $image = file_get_contents($url);

                $img_fullpath = $fullpath . '/' . $plant_id . '_' . uniqid('', true) . $extension;

                $fp = fopen($img_fullpath, 'xb');
                fwrite($fp, $image);
                fclose($fp);

                echo "File size is ok\n";
            } catch (Exception $e) {
                echo($e);

            }
        } else {


        }

    }

}


