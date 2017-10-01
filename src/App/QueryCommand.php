<?php 

namespace App;

require __DIR__."/../../vendor/autoload.php";

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class QueryCommand extends Command
{
    private function read_data($conn, $query)
    {        
        $stmt = $conn->query($query);
        return $stmt->fetchAll();
    }

    private function write_data($filename, $data)
    {
        if (count($data) === 0)
            return;

        try {
            // Open output file (existing file will be overridden)
            $outfs = fopen($filename.'~',"w");
            try {
                fwrite($outfs, implode(';', array_keys($data[0])));
                fwrite($outfs, "\n");
                
                foreach ($data as $row) {                            
                    // We should probably escape ';' character or quote string values (thou it does not appear in the current test dataset)
                    fwrite($outfs, implode(';', array_values($row)));
                    fwrite($outfs, "\n");
                }

                fflush($outfs);
            } finally {
                fclose($outfs);
            }
            rename($filename.'~', $filename);
        } catch (\Exception $e) {
            // Something went wrong and we need to clean up
            if (file_exists($filename.'~'))
                unlink($filename.'~');

            throw $e;
        }

        return true;
    }

    private function delete_data($conn, $data) 
    {
        $conn->beginTransaction();
        try {                
            $stmt = $conn->prepare("delete from users where id = ?");
            foreach ($data as $row) {
                $stmt->bindValue(1, $row["id"]);
                $stmt->execute();
            }
            $conn->commit();
        } catch (\Exception $e) {
            // Something went wrong rollback transaction
            $conn->rollback();
            throw $e;
        }
    }

    protected function configure()
    {
        $this
            ->setName("query")
            ->setDescription("run SQL query from file and store result to file (CSV format).")
            ->addArgument('input', InputArgument::REQUIRED, 'Input SQL file.')
            ->addArgument('output', InputArgument::REQUIRED, 'Output CSV file.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Handle command arguments
        $infilename  = $input->getArgument('input');
        $outfilename = $input->getArgument('output');

        if (!file_exists($infilename))
            throw new \Exception("Input file not found!");
        
        if (file_exists($outfilename))
            unlink($outfilename);

        // Read query from file
        $query = file_get_contents($infilename);
        
        // Configure and connect to database
        $conf = new \Doctrine\DBAL\Configuration();        
        $conn = \Doctrine\DBAL\DriverManager::getConnection([
            "path" => "app.db",
            "driver" => "pdo_sqlite"                
        ], $conf);
        try {
            $rows = $this->read_data($conn, $query);
            
            // Verify that required "id" column is included
            if ( (count($rows) > 0) && !array_key_exists("id", $rows[0]))
                throw new \Exception("Column \"id\" must be selected in query.");
        
            if ($this->write_data($outfilename, $rows))
                $this->delete_data($conn, $rows);
        } finally {
            $conn->close();
        }
    }
}

?>