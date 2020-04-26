<?php
namespace PRKT\TestSearchString;

class SearchStringOnFile
{

    private array $options;
    private string $caseInsensitiveMethod;
    private float $timeStart;
    //private int $ftpConnectID;

    public function __construct(string $searchString, string $inputFile)
    {
        $this->timeStart = microtime(true);

        $this->options = yaml_parse_file("config.yaml");

        if ($this->options["ftp"] !== false) {
            //$this->connectFtp();
            $inputFile = "ftp://{$this->options["ftp_username"]}:{$this->options["ftp_password"]}@{$this->options["ftp"]}" . $inputFile;
        }

        $this->checkInputData($inputFile, $searchString);

        if ($this->options['compare_hash_amounts'] === true) {

            $result = $this->compareHashAmount($inputFile);

        } else {

            if ($this->options['first_occurrence'] === false) {
                $this->options['search_line'] = true;
            }

            if ($this->options['case_insensitive_search'] === true) {
                $this->caseInsensitiveMethod = "strpos";
            } else {
                $this->caseInsensitiveMethod = "stripos";
            }

            if ($this->options['search_line'] === true) {
                $result = $this->searchPositionWithLine($searchString, $inputFile);
            } else {
                $result = $this->searchPosition($searchString, $inputFile);
            }

        }

        $this->printResult($result, $searchString, $this->options['compare_hash_amounts']);

    }

    function __destruct()
    {
        /*if ($this->options["ftp"] !== false) {
            ftp_close($this->ftpConnectID);
        }*/

        $executionTime = microtime(true) - $this->timeStart;
        echo "\n Execution time: " . $executionTime . "\n";
    }

    private function searchPosition(string $searchString, string $inputFile): array
    {

        $caseInsensitiveMethod = $this->caseInsensitiveMethod;

        //if ( $this->options["ftp"] === false ) {
        $inputText = file_get_contents($inputFile);
        //} else {
        //$inputText = $this->getContentByFtp($inputFile);
        //}

        if (($positionSearchString = $caseInsensitiveMethod($inputText, $searchString)) !== false) {

            $result = [
                "successfully" => true,
                "positionSearchString" => $positionSearchString
            ];

        } else {

            $result = [
                "successfully" => false
            ];

        }

        return $result;

    }

    private function searchPositionWithLine(string $searchString, string $inputFile): array
    {

        $caseInsensitiveMethod = $this->caseInsensitiveMethod;

        //if ( $this->options["ftp"] === false ) {
        $inputFileArray = file($inputFile);
        //} else {
        //$inputFileArray = $this->getContentByFtp($inputFile);
        //}

        foreach ($inputFileArray as $numberLine => $textLine) {

            $positionSearchString = -1;

            while (($positionSearchString = $caseInsensitiveMethod($textLine, $searchString,
                    $positionSearchString + 1)) !== false) {
                $result["items"][$numberLine][] = [
                    "positionSearchString" => $positionSearchString,
                    //"numberLine" =>  $numberLine
                ];
                if ($this->options['first_occurrence'] === true) {
                    break;
                }
            }

        }

        if (!empty($result["items"])) {

            $result["successfully"] = true;

        } else {

            $result["successfully"] = false;

        }

        return $result;

    }

    private function compareHashAmount(string $inputFile): array
    {

        $hashAmount = hash_file($this->options["hash_sum_algorithm"], $inputFile, $this->options["hash_bin"]);

        if ($hashAmount === $this->options["hash_amount"]) {
            $result["successfully"] = true;
        } else {
            $result = ["successfully" => false, "hashAmount" => $hashAmount];
        }

        return $result;
    }

    /*private function connectFtp(): void
    {
        if ( $this->options["ftp_ssl"] === true ) {
            $this->ftpConnectID = ftp_ssl_connect($this->options["ftp"], $this->options["ftp_port"]) or exit("Couldn't connect to FTP server '{$this->options["ftp"]}'");
        } else {
            $this->ftpConnectID = ftp_connect($this->options["ftp"], $this->options["ftp_port"]) or exit("Couldn't connect to FTP server '{$this->options["ftp"]}'");
        }

        if ( !ftp_login($this->ftpConnectID, $this->options["ftp_username"], $this->options["ftp_password"]) ) {
            echo "Authorization failed \n";
        }

        ftp_pasv($this->ftpConnectID, true);
    }

    private function getContentByFtp(string $inputFile)
    {
        $handle = fopen('php://temp', 'r+');
        ftp_fget($this->ftpConnectID, $handle, $inputFile, FTP_BINARY, 0);
        $fstats = fstat($handle);
        fseek($handle, 0);
        $inputText = fread($handle, $fstats['size']);
        fclose($handle);

        if ( $this->options['search_line'] === true ) {
            $inputText = explode("\n", $inputFile);
        }

        return $inputText;
    }*/

    private function printResult(array $result, string $searchString, bool $hash): void
    {
        if ($hash == false) {

            if ($result['successfully'] === true) {

                if ($this->options['search_line'] === true) {

                    if ($this->options['first_occurrence'] === false) {
                        echo "The string '$searchString' found: \n";
                        foreach ($result["items"] as $resultItemLine => $resultItems) {
                            echo "\t On line $resultItemLine \n";
                            foreach ($resultItems as $item) {
                                echo "\t\t at position {$item["positionSearchString"]} \n";
                            }
                        }
                    } else {
                        echo "The string '$searchString' found at position " . current($result["items"])[0]["positionSearchString"] . " on line " . key($result["items"]) . " \n";
                    }

                } else {

                    echo "The string '$searchString' found at position {$result["positionSearchString"]} \n";

                }

            } else {
                echo "The string '$searchString' was not found \n";
            }

        } else {

            if ($result['successfully'] === true) {
                echo "Amounts matched \n";
            } else {
                echo "Amounts did not match, file amount = {$result["hashAmount"]}, amount of parameters = {$this->options["hash_amount"]} \n";
            }

        }

    }

    private function checkInputData(string $inputFile, string $searchString)
    {

        /* Check input file */

        if (empty($inputFile)) {
            throw new \Exception("Error: Specify the source file");
        }

        if (!file_exists($inputFile)) {
            throw new \Exception("Error: File does not exist");
        }

        if (!is_file($inputFile)) {
            throw new \Exception("Error: No file specified");
        }

        if (!is_readable($inputFile)) {
            throw new \Exception("Error: Cannot read file");
        }

        if (filesize($inputFile) == 0) {
            throw new \Exception("Error: File is empty");
        }

        if ($this->options["file_max_size"] !== false) {

            $this->options["file_max_size"] = preg_replace("/\s+/", '', $this->options["file_max_size"]);

            $maxSize = preg_replace("/[^0-9]/", '', $this->options["file_max_size"]);
            $unitByte = strtolower(str_replace($maxSize, '', $this->options["file_max_size"]));

            if (empty($unitByte)) {
                if (filesize($inputFile) > $maxSize) {
                    throw new \Exception("Error: File size exceeds maximum '$maxSize' byte");
                }
            } else {
                switch ($unitByte) {
                    case "kb":
                        if (filesize($inputFile) > ($maxSize * 1000)) {
                            throw new \Exception("Error: File size exceeds maximum '$maxSize $unitByte'");
                        }
                        break;
                    case "mb":
                        if (filesize($inputFile) > ($maxSize * pow(1000, 2))) {
                            throw new \Exception("Error: File size exceeds maximum '$maxSize $unitByte'");
                        }
                        break;
                    case "gb":
                        if (filesize($inputFile) > ($maxSize * pow(1000, 3))) {
                            throw new \Exception("Error: File size exceeds maximum '$maxSize $unitByte'");
                        }
                        break;
                    default:
                        throw new \Exception("Error: Indicate the amount of information abbreviated in English (from \"KB\" to \"GB\")");
                }
            }

        }

        if ($this->options["mime-type_constraint"] !== false) {

            if ($this->options["ftp"] === false && mime_content_type($inputFile) !== $this->options["mime-type_constraint"]) {
                throw new \Exception("Error: File contains invalid mime-type, valid mime-type '{$this->options["mime-type_constraint"]}'");
            }

        }


        /* Check input string */

        if ($this->options["compare_hash_amounts"] !== true) {
            if (strlen($searchString) == 0) {
                throw new \Exception("Error: Input string is empty");
            }

            if
            (
                $this->options["minimum_number_of_characters"] !== false &&
                strlen($searchString) < $this->options["minimum_number_of_characters"]
            ) {
                throw new \Exception("Error: The input string is less than the minimum length '{$this->options["minimum_number_of_characters"]}'");
            }
        }


        /* Check hash parameters */

        if ($this->options["compare_hash_amounts"] === true) {

            if ($this->options["hash_sum_algorithm"] !== false) {
                if (empty($this->options["hash_sum_algorithm"])) {
                    throw new \Exception("Error: Specify the hash sum algorithm");
                }

                $this->options["hash_sum_algorithm"] = strtolower($this->options["hash_sum_algorithm"]);

                if (!in_array($this->options["hash_sum_algorithm"], hash_algos())) {
                    throw new \Exception("Error: The algorithm {$this->options["hash_sum_algorithm"]} is not supported");
                }

                if (empty($this->options["hash_amount"])) {
                    throw new \Exception("Error: Specify the hash sum");
                }
            }

        }

    }

}