<?php



class operations
{
    public function get_images($count=1000){


        $rs = array();
        $sql = "SELECT * FROM plants_temp_occur WHERE processed = 0 Limit :limit ";

        try {
            $stmt = $DB->prepare($sql);
            $stmt->bindValue(':limit', (int)$count, PDO::PARAM_INT);

            $stmt->execute();
            $results = $stmt->fetchAll();
        } catch (Exception $ex) {
            echo errorMessage($ex->getMessage());
        }

        return $results;

    }

}
