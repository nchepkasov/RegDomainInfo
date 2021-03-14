<?php

class DomainrClient
{
    const ENDPOINT = "https://domainr.p.rapidapi.com/v2";

    private $Token;

    public function __construct ($Token)
    {
        $this->Token = $Token;
    }    

    public function GetStatus ($Domains)
    {
        $Domains = implode (",", $Domains);

        $curl = curl_init ();

        curl_setopt_array ($curl, [
            CURLOPT_URL => self::ENDPOINT . "/status?mashape-key={$this->Token}&domain={$Domains}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "x-rapidapi-host: domainr.p.rapidapi.com",
                "x-rapidapi-key: {$this->Token}"
            ],
        ]);
        
        $response = curl_exec ($curl);
        $err = curl_error ($curl);
        
        curl_close ($curl);

        if ($err) {
            throw new \Exception ("cURL Error #:" . $err);
        } else {
            return json_decode ($response, true) ['status'];
        }
    }
}