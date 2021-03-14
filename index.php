<?php 

require_once ('vendor/autoload.php');

require_once ('api/ahrefs.class.php');
require_once ('api/domainr.class.php');

require_once ('settings.php');


function index_by (&$Array, $Column)
{
    $Array = array_combine (
        array_column ($Array, $Column),
        $Array
    );
}

/*****************************/

use LayerShifter\TLDExtract\Extract;

$TLDExtract = new Extract(null, null, Extract::MODE_ALLOW_ICANN);

$Domains = [];

$LinkedDomains = [];  $Errors = [];  $RegDomainList = [];

/*****************************/

if (@$_POST ['action'] == 'GetStatus')
{
    set_time_limit (1200);

    $Domains = array_filter (explode ("\n", str_replace ("\r", "", $_POST ['Domains'])));
}

$ExcludeActive = filter_var (@$_POST ['ExcludeActive'], FILTER_VALIDATE_BOOLEAN);

$DomainsText   = htmlspecialchars (@$_POST ['Domains']);

/*****************************/

$Ahrefs  = new AhrefsClient  ($Settings ['ahrefs_token']);
$Domainr = new DomainrClient ($Settings ['rapidapi_token']);

foreach ($Domains as $Domain)
{
    try
    {
        $LinkedDomains [$Domain] = $Ahrefs->GetLinkedDomains ($Domain, $Settings ['max_linked_domains']);

        foreach ($LinkedDomains [$Domain] as &$d)
        {
            $d ['domain_to_orig'] = $d ['domain_to'];
        }

        unset ($d);
    }
    catch (\Exception $e)
    {
        $LinkedDomains [$Domain] = null;

        $Errors[] = $Domain . ": " . $e->getMessage ();
        
        continue;
    }

    index_by ($LinkedDomains [$Domain], 'domain_to_orig');
    
    foreach (array_chunk ($LinkedDomains [$Domain], 10) as $Next10Domains)
        /* NC: Doesn't work for >10 domains at once */
    {
        foreach ($Next10Domains as $i => &$d)
        {
            $SecondLevelDomain = $TLDExtract->parse ($d ['domain_to'])->getRegistrableDomain ();

            if ($SecondLevelDomain == null)
            {
                unset ($LinkedDomains [$Domain][$d ['domain_to']]);
                unset ($Next10Domains [$i]);
            }

            else if ($d ['domain_to'] != $SecondLevelDomain)
            {
                $LinkedDomains [$Domain][$d ['domain_to']]['domain_to'] = $SecondLevelDomain;

                $d ['domain_to'] = $SecondLevelDomain;
            }
        }

        unset ($d);

        try
        {
            $DomainStatus = $Domainr->GetStatus (
                array_column ($Next10Domains, 'domain_to')
            );
        }
        catch (\Exception $e)
        {
            $Errors [] = $e->getMessage ();

            continue;
        }
        
        foreach ($DomainStatus as $ds)
        {
            if ($ExcludeActive && in_array ('active', explode (' ', $ds ['status']))) 
            {
                foreach ($LinkedDomains [$Domain] as $i => $d)
                {
                    if ($ds ['domain'] == $d ['domain_to'])
                    {
                        unset ($LinkedDomains [$Domain][$i]);
                    }
                }
                
                continue;
            }
            
            if (isset ($ds ['domain'])) 
            {
                foreach ($LinkedDomains [$Domain] as &$d)
                {
                    if ($ds ['domain'] == $d ['domain_to'])
                    {
                        $d ['status'] = $ds ['status'];
                    }
                }

                unset ($d);
            }
        }
    }
}

/*****************************/

if (@$_POST ['Ajax'])
{
    echo json_encode ([
        'Errors' => $Errors,
        'Domains' => $LinkedDomains
    ]);
}
else
{
    $ViewData = [
        'Errors' => $Errors,
        'Domains' => $LinkedDomains,
        'DomainsText' => $DomainsText,
        'ExcludeActive' => $ExcludeActive
    ];
    
    require_once ('views/view.phtml');    
}
