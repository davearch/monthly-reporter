<?php

require_once(__DIR__ . '/vendor/autoload.php');
use QuickBooksOnline\API\DataService\DataService;
use QuickBooksOnline\API\ReportService\ReportService;
use QuickBooksOnline\API\ReportService\ReportName;

session_start();

function object_to_array($obj) {
    $arr = is_object($obj) ? get_object_vars($obj) : $obj;
    foreach ($arr as $key => $val) {
            $val = (is_array($val) || is_object($val)) ? object_to_array($val) : $val;
            $arr[$key] = $val;
    }
    return $arr;
}

function makeAPICall( $type = NULL )
{
    // Create SDK instance
    //$config = include('config.php');
    $dataService = DataService::Configure(array(
        'auth_mode' => 'oauth2',
        'ClientID' => $_ENV['client_id'],
        'ClientSecret' =>  $_ENV['client_secret'],
        'RedirectURI' => $_ENV['oauth_redirect_uri'],
        'scope' => $_ENV['oauth_scope'],
        'baseUrl' => "production"
    ));

    $serviceContext = $dataService->getServiceContext();
    // Prep Data Services
    $reportService = new ReportService($serviceContext);
    if (!$reportService) {
        exit("Problem while initializing ReportService.\n");
    }


    /*
     * Retrieve the accessToken value from session variable
     */
    $accessToken = $_SESSION['sessionAccessToken'];

    /*
     * Update the OAuth2Token of the dataService object
     */
    $dataService->updateOAuth2Token($accessToken);

    $dataService->throwExceptionOnError(true);

    if ($type == 'companyInfo') {
        $companyInfo = $dataService->getCompanyInfo();
        $address = "QBO API call Successful!! Response Company name: " . $companyInfo->CompanyName . " Company Address: " . $companyInfo->CompanyAddr->Line1 . " " . $companyInfo->CompanyAddr->City . " " . $companyInfo->CompanyAddr->PostalCode;
        print_r($address);
        return $companyInfo;
    } elseif ($type == 'class') {
        $classInfo = $dataService->findAll("class");
        print_r($classInfo);
        return $classInfo;
    } elseif ($type == 'report') {
        //$reportInfo = $dataService->FindById("class", 5000000000000111940);
        $reportService->setStartDate("2019-04-01");
        $reportService->setEndDate("2019-10-31");
        $reportService->setAccountingMethod("Cash");
        $reportService->setClassId("900000000000363117");
        $profitAndLossReport = $reportService->executeReport(ReportName::PROFITANDLOSS);
        if (!$profitAndLossReport) {
            exit("ProfitAndLossReport Is Null.\n");
        } else {
            $reportName = strtolower($profitAndLossReport->Header->ReportName);
            echo("ReportName: " . $reportName . "\n");
            echo("Profit And Loss Report Execution Successful!" . "\n");
            echo("\n");
            echo(json_encode($profitAndLossReport, true));
            echo("\n");

            /*
            echo("downloading...\n");
            $fileName = 'example.csv';
            //Set the Content-Type and Content-Disposition headers.
            header('Content-Type: application/excel');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            //$fp = fopen('php://output', 'w');
            $download_dir = './log_report_folder/results.csv';
            $fp = fopen($download_dir, 'w');
            $modified_profitAndLossReport = object_to_array($profitAndLossReport);
            foreach ($modified_profitAndLossReport as $file) {
                $result = [];
                array_walk_recursive($file, function($item) use (&$result) {
                    $result[] = $item;
                });
                fputcsv($fp, $result);
            }
            fclose($fp);
            echo "csv written to disk...";
            */

            
            $downloadDirectory = './log_report_folder/results.json';
            echo("Writing to disk...");
            $fp = fopen($downloadDirectory, 'w');
            fwrite($fp, json_encode($profitAndLossReport, true));
            fclose($fp);

            $address = $reportName . " written to " . $downloadDirectory;
            print_r($address);
            
            
            // $directoryForThePDF = $dataService->DownloadPDF($profitAndLossReport, "./log_report_folder");
            // echo "PDF is download at: " .$directoryForThePDF;

        }
    }
}
$type = $_GET['type'];
$result = makeAPICall( $type );

?>
