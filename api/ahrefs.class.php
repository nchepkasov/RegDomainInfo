<?php

class AhrefsClient 
{
    const ENDPOINT = "https://apiv2.ahrefs.com";
    
    private $Token;

    public function __construct ($Token)
    {
        $this->Token = $Token;
    }

    public function GetLinkedDomains ($Target, $Limit)
    {
        $Response = json_decode (
            file_get_contents (
                self::ENDPOINT . "/?from=linked_domains&target={$Target}&mode=domain&limit={$Limit}&output=json&token={$this->Token}"
            ), true
        );

        if (isset ($Response ['error'])) {
            throw new \Exception ($Response ['error']);
        }

        if (isset ($Response ['domains'])) {
            return $Response ['domains'];
        }

        return null;
    }
}