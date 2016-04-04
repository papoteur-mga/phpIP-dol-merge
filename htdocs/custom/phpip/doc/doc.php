<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Brian Fraval         <brian@fraval.org>
 * Copyright (C) 2004-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2008      Patrick Raguin       <patrick.raguin@auguria.net>
 * Copyright (C) 2010-2014 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2011-2013 Alexandre Spangaro   <alexandre.spangaro@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/societe/soc.php
 *  \ingroup    societe
 *  \brief      Third party card page
 */

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/phpip/core/modules/html.formfile_ip.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/phpip/class/matter.class.php';
if (! empty($conf->adherent->enabled)) require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';

$langs->load("companies");
$langs->load("commercial");
$langs->load("bills");
$langs->load("banks");
$langs->load("users");
if (! empty($conf->notification->enabled)) $langs->load("mails");

$mesg=''; $error=0; $errors=array();

$action         = (GETPOST('action') ? GETPOST('action') : 'view');
$backtopage = GETPOST('backtopage','alpha');
$confirm        = GETPOST('confirm');
$socid          = GETPOST('socid','int');
if ($user->societe_id) $socid=$user->societe_id;
if (empty($socid) && $action == 'view') $action='create';


$object = new Societe($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels=$extrafields->fetch_name_optionals_label($object->table_element);

// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
$object->getCanvas($socid);
$canvas = $object->canvas?$object->canvas:GETPOST("canvas");
$objcanvas=null;
if (! empty($canvas))
{
    require_once DOL_DOCUMENT_ROOT.'/core/class/canvas.class.php';
    $objcanvas = new Canvas($db, $action);
    $objcanvas->getCanvas('thirdparty', 'card', $canvas);
}

// Security check
$result = restrictedArea($user, 'societe', $socid, '&societe', '', 'fk_soc', 'rowid', $objcanvas);

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('thirdpartycard','globalcard'));


/*
 * Actions
 */

$parameters=array('id'=>$socid, 'objcanvas'=>$objcanvas);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
    if (GETPOST('getcustomercode'))
    {
        // We defined value code_client
        $_POST["code_client"]="Acompleter";
    }

    if (GETPOST('getsuppliercode'))
    {
        // We defined value code_fournisseur
        $_POST["code_fournisseur"]="Acompleter";
    }

    if($action=='set_localtax1')
    {
        //obtidre selected del combobox
        $value=GETPOST('lt1');
        $object = new Societe($db);
        $object->fetch($socid);
        $res=$object->setValueFrom('localtax1_value', $value);
    }
    if($action=='set_localtax2')
    {
        //obtidre selected del combobox
        $value=GETPOST('lt2');
        $object = new Societe($db);
        $object->fetch($socid);
        $res=$object->setValueFrom('localtax2_value', $value);
    }

    // Add new or update third party
    if ((! GETPOST('getcustomercode') && ! GETPOST('getsuppliercode'))
    && ($action == 'add' || $action == 'update') && $user->rights->societe->creer)
    {
        require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

        if ($action == 'update')
        {
                $ret=$object->fetch($socid);
                $object->oldcopy=dol_clone($object);
        }
                else $object->canvas=$canvas;

        if (GETPOST("private") == 1)
        {
            $object->particulier       = GETPOST("private");

            $object->name              = dolGetFirstLastname(GETPOST('firstname','alpha'),GETPOST('nom','alpha')?GETPOST('nom','alpha'):GETPOST('name','alpha'));
            $object->civility_id       = GETPOST('civility_id', 'int');
            // Add non official properties
            $object->name_bis          = GETPOST('name','alpha')?GETPOST('name','alpha'):GETPOST('nom','alpha');
            $object->firstname         = GETPOST('firstname','alpha');
        }
        else
        {
            $object->name              = GETPOST('name', 'alpha')?GETPOST('name', 'alpha'):GETPOST('nom', 'alpha');
        }
        $object->address               = GETPOST('address', 'alpha');
        $object->zip                   = GETPOST('zipcode', 'alpha');
        $object->town                  = GETPOST('town', 'alpha');
        $object->country_id            = GETPOST('country_id', 'int');
        $object->state_id              = GETPOST('state_id', 'int');
        $object->skype                 = GETPOST('skype', 'alpha');
        $object->phone                 = GETPOST('phone', 'alpha');
        $object->fax                   = GETPOST('fax','alpha');
        $object->email                 = GETPOST('email', 'custom', 0, FILTER_SANITIZE_EMAIL);
        $object->url                   = GETPOST('url', 'custom', 0, FILTER_SANITIZE_URL);
        $object->idprof1               = GETPOST('idprof1', 'alpha');
        $object->idprof2               = GETPOST('idprof2', 'alpha');
        $object->idprof3               = GETPOST('idprof3', 'alpha');
        $object->idprof4               = GETPOST('idprof4', 'alpha');
        $object->idprof5               = GETPOST('idprof5', 'alpha');
        $object->idprof6               = GETPOST('idprof6', 'alpha');
        $object->prefix_comm           = GETPOST('prefix_comm', 'alpha');
        $object->code_client           = GETPOST('code_client', 'alpha');
        $object->code_fournisseur      = GETPOST('code_fournisseur', 'alpha');
        $object->capital               = GETPOST('capital', 'alpha');
        $object->barcode               = GETPOST('barcode', 'alpha');

        $object->tva_intra             = GETPOST('tva_intra', 'alpha');
        $object->tva_assuj             = GETPOST('assujtva_value', 'alpha');
        $object->status                = GETPOST('status', 'alpha');

        // Local Taxes
        $object->localtax1_assuj       = GETPOST('localtax1assuj_value', 'alpha');
        $object->localtax2_assuj       = GETPOST('localtax2assuj_value', 'alpha');

        $object->localtax1_value           = GETPOST('lt1', 'alpha');
        $object->localtax2_value           = GETPOST('lt2', 'alpha');

        $object->forme_juridique_code  = GETPOST('forme_juridique_code', 'int');
        $object->effectif_id           = GETPOST('effectif_id', 'int');
        $object->typent_id             = GETPOST('typent_id');

        $object->client                = GETPOST('client', 'int');
        $object->fournisseur           = GETPOST('fournisseur', 'int');

        $object->commercial_id         = GETPOST('commercial_id', 'int');
        $object->default_lang          = GETPOST('default_lang');

        // Webservices url/key
        $object->webservices_url       = GETPOST('webservices_url', 'custom', 0, FILTER_SANITIZE_URL);
        $object->webservices_key       = GETPOST('webservices_key', 'san_alpha');

        // Fill array 'array_options' with data from add form
        $ret = $extrafields->setOptionalsFromPost($extralabels,$object);
                if ($ret < 0)
                {
                         $error++;
                         $action = ($action=='add'?'create':'edit'); 
                }

        if (GETPOST('deletephoto')) $object->logo = '';
        else if (! empty($_FILES['photo']['name'])) $object->logo = dol_sanitizeFileName($_FILES['photo']['name']);

        // Check parameters
        if (! GETPOST("cancel"))
        {
            if (! empty($object->email) && ! isValidEMail($object->email))
            {
                $langs->load("errors");
                $error++; $errors[] = $langs->trans("ErrorBadEMail",$object->email);
                $action = ($action=='add'?'create':'edit');
            }
            if (! empty($object->url) && ! isValidUrl($object->url))
            {
                $langs->load("errors");
                $error++; $errors[] = $langs->trans("ErrorBadUrl",$object->url);
                $action = ($action=='add'?'create':'edit');
            }
            if ($object->fournisseur && ! $conf->fournisseur->enabled)
            {
                $langs->load("errors");
                $error++; $errors[] = $langs->trans("ErrorSupplierModuleNotEnabled");
                $action = ($action=='add'?'create':'edit');
            }
            if (! empty($object->webservices_url)) {
                //Check if has transport, without any the soap client will give error
                if (strpos($object->webservices_url, "http") === false)
                {
                    $object->webservices_url = "http://".$object->webservices_url;
                }
                if (! isValidUrl($object->webservices_url)) {
                    $langs->load("errors");
                    $error++; $errors[] = $langs->trans("ErrorBadUrl",$object->webservices_url);
                    $action = ($action=='add'?'create':'edit');
                }
            }

            // We set country_id, country_code and country for the selected country
            $object->country_id=GETPOST('country_id')!=''?GETPOST('country_id'):$mysoc->country_id;
            if ($object->country_id)
            {
                $tmparray=getCountry($object->country_id,'all');
                $object->country_code=$tmparray['code'];
                $object->country=$tmparray['label'];
            }

            // Check for duplicate or mandatory prof id
                for ($i = 1; $i < 5; $i++)
                {
                    $slabel="idprof".$i;
                        $_POST[$slabel]=trim($_POST[$slabel]);
                    $vallabel=$_POST[$slabel];
                        if ($vallabel && $object->id_prof_verifiable($i))
                                {
                                        if($object->id_prof_exists($i,$vallabel,$object->id))
                                        {
                                                $langs->load("errors");
                                $error++; $errors[] = $langs->transcountry('ProfId'.$i, $object->country_code)." ".$langs->trans("ErrorProdIdAlreadyExist", $vallabel);
                                $action = (($action=='add'||$action=='create')?'create':'edit');
                                        }
                                }

                                $idprof_mandatory ='SOCIETE_IDPROF'.($i).'_MANDATORY';

                                if (! $vallabel && ! empty($conf->global->$idprof_mandatory))
                                {
                                        $langs->load("errors");
                                        $error++;
                                        $errors[] = $langs->trans("ErrorProdIdIsMandatory", $langs->transcountry('ProfId'.$i, $object->country_code));
                                        $action = (($action=='add'||$action=='create')?'create':'edit');
                                }
                }
        }

        if (! $error)
        {
            if ($action == 'add')
            {
                $db->begin();

                if (empty($object->client))      $object->code_client='';
                if (empty($object->fournisseur)) $object->code_fournisseur='';

                $result = $object->create($user);
                if ($result >= 0)
                {
                    if ($object->particulier)
                    {
                        dol_syslog("This thirdparty is a personal people",LOG_DEBUG);
                        $result=$object->create_individual($user);
                        if (! $result >= 0)
                        {
                            $error=$object->error; $errors=$object->errors;
                        }
                    }

                    // Logo/Photo save
                    $dir     = $conf->societe->multidir_output[$conf->entity]."/".$object->id."/logos/";
                    $file_OK = is_uploaded_file($_FILES['photo']['tmp_name']);
                    if ($file_OK)
                    {
                        if (image_format_supported($_FILES['photo']['name']))
                        {
                            dol_mkdir($dir);

                            if (@is_dir($dir))
                            {
                                $newfile=$dir.'/'.dol_sanitizeFileName($_FILES['photo']['name']);
                                $result = dol_move_uploaded_file($_FILES['photo']['tmp_name'], $newfile, 1);

                                if (! $result > 0)
                                {
                                    $errors[] = "ErrorFailedToSaveFile";
                                }
                                else
                                {
                                    // Create small thumbs for company (Ratio is near 16/9)
                                    // Used on logon for example
                                    $imgThumbSmall = vignette($newfile, $maxwidthsmall, $maxheightsmall, '_small', $quality);

                                    // Create mini thumbs for company (Ratio is near 16/9)
                                    // Used on menu or for setup page for example
                                    $imgThumbMini = vignette($newfile, $maxwidthmini, $maxheightmini, '_mini', $quality);
                                }
                            }
                        }
                    }
                    else
                      {
                                                switch($_FILES['photo']['error'])
                                                {
                                                    case 1: //uploaded file exceeds the upload_max_filesize directive in php.ini
                                                    case 2: //uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the html form
                                                      $errors[] = "ErrorFileSizeTooLarge";
                                                      break;
                                                case 3: //uploaded file was only partially uploaded
                                                      $errors[] = "ErrorFilePartiallyUploaded";
                                                      break;
                                                }
                        }
                    // Gestion du logo de la société
                }
                else
                                {
                    $error=$object->error; $errors=$object->errors;
                }

                if ($result >= 0)
                {
                    $db->commit();

                        if (! empty($backtopage))
                        {
                            header("Location: ".$backtopage);
                        exit;
                        }
                        else
                        {
                        $url=$_SERVER["PHP_SELF"]."?socid=".$object->id;
                        if (($object->client == 1 || $object->client == 3) && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS)) $url=DOL_URL_ROOT."/comm/card.php?socid=".$object->id;
                        else if ($object->fournisseur == 1) $url=DOL_URL_ROOT."/fourn/card.php?socid=".$object->id;

                                header("Location: ".$url);
                        exit;
                        }
                }
                else
                {
                    $db->rollback();
                    $action='create';
                }
            }

            if ($action == 'update')
            {
                if (GETPOST("cancel"))
                {
                        if (! empty($backtopage))
                        {
                            header("Location: ".$backtopage);
                        exit;
                        }
                        else
                        {
                            header("Location: ".$_SERVER["PHP_SELF"]."?socid=".$socid);
                        exit;
                        }
                }

                // To not set code if third party is not concerned. But if it had values, we keep them.
                if (empty($object->client) && empty($object->oldcopy->code_client))          $object->code_client='';
                if (empty($object->fournisseur)&& empty($object->oldcopy->code_fournisseur)) $object->code_fournisseur='';
                //var_dump($object);exit;

                $result = $object->update($socid, $user, 1, $object->oldcopy->codeclient_modifiable(), $object->oldcopy->codefournisseur_modifiable(), 'update', 0);
                if ($result <=  0)
                {
                    $error = $object->error; $errors = $object->errors;
                }

                // Logo/Photo save
                $dir     = $conf->societe->multidir_output[$object->entity]."/".$object->id."/logos";
                $file_OK = is_uploaded_file($_FILES['photo']['tmp_name']);
                if ($file_OK)
                {
                    if (GETPOST('deletephoto'))
                    {
                        $fileimg=$dir.'/'.$object->logo;
                        $dirthumbs=$dir.'/thumbs';
                        dol_delete_file($fileimg);
                        dol_delete_dir_recursive($dirthumbs);
                    }

                    if (image_format_supported($_FILES['photo']['name']) > 0)
                    {
                        dol_mkdir($dir);

                        if (@is_dir($dir))
                        {
                            $newfile=$dir.'/'.dol_sanitizeFileName($_FILES['photo']['name']);
                            $result = dol_move_uploaded_file($_FILES['photo']['tmp_name'], $newfile, 1);

                            if (! $result > 0)
                            {
                                $errors[] = "ErrorFailedToSaveFile";
                            }
                            else
                            {
                                // Create small thumbs for company (Ratio is near 16/9)
                                // Used on logon for example
                                $imgThumbSmall = vignette($newfile, $maxwidthsmall, $maxheightsmall, '_small', $quality);

                                // Create mini thumbs for company (Ratio is near 16/9)
                                // Used on menu or for setup page for example
                                $imgThumbMini = vignette($newfile, $maxwidthmini, $maxheightmini, '_mini', $quality);
                            }
                        }
                    }
                    else
                                        {
                        $errors[] = "ErrorBadImageFormat";
                    }
                }
                else
              {
                                        switch($_FILES['photo']['error'])
                                        {
                                            case 1: //uploaded file exceeds the upload_max_filesize directive in php.ini
                                            case 2: //uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the html form
                                              $errors[] = "ErrorFileSizeTooLarge";
                                              break;
                                        case 3: //uploaded file was only partially uploaded
                                              $errors[] = "ErrorFilePartiallyUploaded";
                                              break;
                                        }
                }
                // Gestion du logo de la société


                // Update linked member
                if (! $error && $object->fk_soc > 0)
                {

                        $sql = "UPDATE ".MAIN_DB_PREFIX."adherent";
                        $sql.= " SET fk_soc = NULL WHERE fk_soc = " . $id;
                        if (! $object->db->query($sql))
                        {
                                $error++;
                                $object->error .= $object->db->lasterror();
                        }
                }

                if (! $error && ! count($errors))
                {
                    if (! empty($backtopage))
                        {
                            header("Location: ".$backtopage);
                        exit;
                        }
                        else
                        {
                            header("Location: ".$_SERVER["PHP_SELF"]."?socid=".$socid);
                        exit;
                        }
                }
                else
                {
                    $object->id = $socid;
                    $action= "edit";
                }
            }
        }
    }

    // Delete third party
    if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->societe->supprimer)
    {
        $object->fetch($socid);
        $result = $object->delete($socid);

        if ($result > 0)
        {
            header("Location: ".DOL_URL_ROOT."/societe/societe.php?delsoc=".urlencode($object->name));
            exit;
        }
        else
        {
            $langs->load("errors");
            $error=$langs->trans($object->error); $errors = $object->errors;
            $action='';
        }
    }

    // Set parent company
    if ($action == 'set_thirdparty' && $user->rights->societe->creer)
    {
        $result = $object->set_parent(GETPOST('editparentcompany','int'));
    }


    // Actions to send emails
    $id=$socid;
    $actiontypecode='AC_OTH_AUTO';
    $trigger_name='COMPANY_SENTBYMAIL';
    $paramname='socid';
    $mode='emailfromthirdparty';
    include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';


    /*
     * Generate document
     */
    if ($action == 'builddoc')  // En get ou en post
    {
        if (is_numeric(GETPOST('model')))
        {
            $error=$langs->trans("ErrorFieldRequired",$langs->transnoentities("Model"));
        }
        else
        {
            require_once DOL_DOCUMENT_ROOT.'/core/modules/societe/modules_societe.class.php';

            //$object->fetch($socid);
            $matter = new Matter($db, $socid);

            $matter->fetch_matter(GETPOST('matter','alpha'));
            dol_syslog('Genere doc pour '.$matter->matter_id.$matter->matter_country);

            // Define output language
            $outputlangs = $langs;
            $newlang='';
            if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id')) $newlang=GETPOST('lang_id');
            if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$fac->client->default_lang;
            if (! empty($newlang))
            {
                $outputlangs = new Translate("",$conf);
                $outputlangs->setDefaultLang($newlang);
            }
            $result=thirdparty_doc_create($db, $matter, '', GETPOST('model','alpha'),  $outputlangs);
            if ($result <= 0)
            {
                dol_print_error($db,$result);
                exit;
            }
        }
    }

    // Remove file in doc form
    else if ($action == 'remove_file')
    {
        if ($object->fetch($socid))
        {
                require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

                $langs->load("other");
                $upload_dir = $conf->societe->dir_output;
                $file = $upload_dir . '/' . GETPOST('file');
                $ret=dol_delete_file($file,0,0,0,$object);
                if ($ret) setEventMessage($langs->trans("FileWasRemoved", GETPOST('urlfile')));
                else setEventMessage($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), 'errors');
        }
    }
}



/*
 *  View
 */

$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('',$langs->trans("ThirdParty"),$help_url);

$form = new Form($db);
$formfile = new FormFile($db);
$formadmin = new FormAdmin($db);
$formcompany = new FormCompany($db);

$countrynotdefined=$langs->trans("ErrorSetACountryFirst").' ('.$langs->trans("SeeAbove").')';

if (is_object($objcanvas) && $objcanvas->displayCanvasExists($action))
{
    // -----------------------------------------
    // When used with CANVAS
    // -----------------------------------------
    if (empty($object->error) && $socid)
        {
             $object = new Societe($db);
             $result=$object->fetch($socid);
             if ($result <= 0) dol_print_error('',$object->error);
        }
        $objcanvas->assign_values($action, $object->id, $object->ref);  // Set value for templates
    $objcanvas->display_canvas($action);                                                        // Show template
}
else
{
    // -----------------------------------------
    // View
    // -----------------------------------------

    {
        /*
         * View
         */
        $object = new Societe($db);
        $res=$object->fetch($socid);
        if ($res < 0) { dol_print_error($db,$object->error); exit; }
        $res=$object->fetch_optionals($object->id,$extralabels);
        //if ($res < 0) { dol_print_error($db); exit; }


        $head = societe_prepare_head($object);

        dol_fiche_head($head, 'phpip', $langs->trans("ThirdParty"),0,'company');

        // Confirm delete third party
        if ($action == 'delete' || ($conf->use_javascript_ajax && empty($conf->dol_use_jmobile)))
        {
            print $form->formconfirm($_SERVER["PHP_SELF"]."?socid=".$object->id,$langs->trans("DeleteACompany"),$langs->trans("ConfirmDeleteCompany"),"confirm_delete",'',0,"action-delete");
        }

        dol_htmloutput_errors($error,$errors);

        $showlogo=$object->logo;
        $showbarcode=empty($conf->barcode->enabled)?0:1;
        if (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->barcode->lire_advance)) $showbarcode=0;

        print '<table class="border" width="100%">';

        // Ref
        /*
        print '<tr><td width="25%" valign="top">'.$langs->trans("Ref").'</td>';
        print '<td colspan="2">';
        print $fuser->id;
        print '</td>';
        print '</tr>';
        */

        // Name
        print '<tr><td width="25%">'.$langs->trans('ThirdPartyName').'</td>';
        print '<td colspan="3">';
        print $form->showrefnav($object, 'socid', '', ($user->societe_id?0:1), 'rowid', 'nom');
        print '</td>';
        print '</tr>';

        // Logo+barcode
        $rowspan=6;
        if (! empty($conf->global->SOCIETE_USEPREFIX)) $rowspan++;
        if (! empty($object->client)) $rowspan++;
        if (! empty($conf->fournisseur->enabled) && $object->fournisseur && ! empty($user->rights->fournisseur->lire)) $rowspan++;
        if (! empty($conf->barcode->enabled)) $rowspan++;
        if (empty($conf->global->SOCIETE_DISABLE_STATE)) $rowspan++;
        $htmllogobar='';
        if ($showlogo || $showbarcode)
        {
            $htmllogobar.='<td rowspan="'.$rowspan.'" style="text-align: center;" width="25%">';
            if ($showlogo)   $htmllogobar.=$form->showphoto('societe',$object);
            if ($showlogo && $showbarcode) $htmllogobar.='<br><br>';
            if ($showbarcode) $htmllogobar.=$form->showbarcode($object);
            $htmllogobar.='</td>';
        }

        // Prefix
        if (! empty($conf->global->SOCIETE_USEPREFIX))  // Old not used prefix field
        {
            print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="'.(2+(($showlogo || $showbarcode)?0:1)).'">'.$object->prefix_comm.'</td>';
            print $htmllogobar; $htmllogobar='';
            print '</tr>';
        }

        // Barcode
        if (! empty($conf->barcode->enabled))
        {
            print '<tr><td>';
            print $langs->trans('Gencod').'</td><td colspan="'.(2+(($showlogo || $showbarcode)?0:1)).'">'.$object->barcode;
            print '</td>';
            print $htmllogobar; $htmllogobar='';
            print '</tr>';
        }


        // Address
        print "<tr><td valign=\"top\">".$langs->trans('Address').'</td><td colspan="'.(2+(($showlogo || $showbarcode)?0:1)).'">';
        dol_print_address($object->address,'gmap','thirdparty',$object->id);
        print "</td></tr>";

        // Zip / Town
        print '<tr><td width="25%">'.$langs->trans('Zip').' / '.$langs->trans("Town").'</td><td colspan="'.(2+(($showlogo || $showbarcode)?0:1)).'">';
        print $object->zip.($object->zip && $object->town?" / ":"").$object->town;
        print "</td>";
        print '</tr>';

        // Country
        print '<tr><td>'.$langs->trans("Country").'</td><td colspan="'.(2+(($showlogo || $showbarcode)?0:1)).'" class="nowrap">';
        if (! empty($object->country_code))
        {
                //$img=picto_from_langcode($object->country_code);
                $img='';
                if ($object->isInEEC()) print $form->textwithpicto(($img?$img.' ':'').$object->country,$langs->trans("CountryIsInEEC"),1,0);
                else print ($img?$img.' ':'').$object->country;
        }
        print '</td></tr>';

        // EMail
        print '<tr><td>'.$langs->trans('EMail').'</td><td colspan="'.(2+(($showlogo || $showbarcode)?0:1)).'">';
        print dol_print_email($object->email,0,$object->id,'AC_EMAIL');
        print '</td></tr>';


        // Default language
        if (! empty($conf->global->MAIN_MULTILANGS))
        {
            require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
            print '<tr><td>'.$langs->trans("DefaultLang").'</td><td colspan="3">';
            //$s=picto_from_langcode($object->default_lang);
            //print ($s?$s.' ':'');
            $langs->load("languages");
            $labellang = ($object->default_lang?$langs->trans('Language_'.$object->default_lang):'');
            print $labellang;
            print '</td></tr>';
        }

        // Parent company
        if (empty($conf->global->SOCIETE_DISABLE_PARENTCOMPANY))
        {
                // Payment term
                print '<tr><td>';
                print '<table class="nobordernopadding" width="100%"><tr><td>';
                print $langs->trans('ParentCompany');
                print '</td>';
                if ($action != 'editparentcompany') print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editparentcompany&amp;socid='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('Edit'),1).'</a></td>';
                print '</tr></table>';
                print '</td><td colspan="3">';
                if ($action == 'editparentcompany')
                {
                        $form->form_thirdparty($_SERVER['PHP_SELF'].'?socid='.$object->id,$object->parent,'editparentcompany','s.rowid <> '.$object->id,1);
                }
                else
                {
                        $form->form_thirdparty($_SERVER['PHP_SELF'].'?socid='.$object->id,$object->parent,'none','s.rowid <> '.$object->id,1);
                }
                print '</td>';
                print '</tr>';
        }

        // Sales representative
        include DOL_DOCUMENT_ROOT.'/societe/tpl/linesalesrepresentative.tpl.php';


        print '</table>';

        dol_fiche_end();


        /*
         *  Actions
         */
        print '<div class="tabsAction">'."\n";

                $parameters=array();
                $reshook=$hookmanager->executeHooks('addMoreActionsButtons',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
                if (empty($reshook))
                {
                if (! empty($object->email))
                {
                        $langs->load("mails");
                        print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?socid='.$object->id.'&amp;action=presend&amp;mode=init">'.$langs->trans('SendMail').'</a></div>';
                }
                else
                        {
                        $langs->load("mails");
                        print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NoEMail")).'">'.$langs->trans('SendMail').'</a></div>';
                }

                }

        print '</div>'."\n";


                if ($action == 'presend')
                {
                        /*
                         * Affiche formulaire mail
                        */

                        // By default if $action=='presend'
                        $titreform='SendMail';
                        $topicmail='';
                        $action='send';
                        $modelmail='thirdparty';

                        print '<br>';
                        print_titre($langs->trans($titreform));

                        // Define output language
                        $outputlangs = $langs;
                        $newlang = '';
                        if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id']))
                                $newlang = $_REQUEST['lang_id'];
                        if ($conf->global->MAIN_MULTILANGS && empty($newlang))
                                $newlang = $object->client->default_lang;

                        // Cree l'objet formulaire mail
                        include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
                        $formmail = new FormMail($db);
                        $formmail->param['langsmodels']=(empty($newlang)?$langs->defaultlang:$newlang);
                        $formmail->fromtype = 'user';
                        $formmail->fromid   = $user->id;
                        $formmail->fromname = $user->getFullName($langs);
                        $formmail->frommail = $user->email;
                        $formmail->withfrom=1;
                        $formmail->withtopic=1;
                        $liste=array();
                        foreach ($object->thirdparty_and_contact_email_array(1) as $key=>$value) $liste[$key]=$value;
                        $formmail->withto=GETPOST('sendto')?GETPOST('sendto'):$liste;
                        $formmail->withtofree=0;
                        $formmail->withtocc=$liste;
                        $formmail->withtoccc=$conf->global->MAIN_EMAIL_USECCC;
                        $formmail->withfile=2;
                        $formmail->withbody=1;
                        $formmail->withdeliveryreceipt=1;
                        $formmail->withcancel=1;
                        // Tableau des substitutions
                        $formmail->substit['__SIGNATURE__']=$user->signature;
                        $formmail->substit['__PERSONALIZED__']='';
                        $formmail->substit['__CONTACTCIVNAME__']='';

                        //Find the good contact adress
                        /*
                        $custcontact='';
                        $contactarr=array();
                        $contactarr=$object->liste_contact(-1,'external');

                        if (is_array($contactarr) && count($contactarr)>0)
                        {
                        foreach($contactarr as $contact)
                        {
                        if ($contact['libelle']==$langs->trans('TypeContact_facture_external_BILLING')) {

                        require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';

                        $contactstatic=new Contact($db);
                        $contactstatic->fetch($contact['id']);
                        $custcontact=$contactstatic->getFullName($langs,1);
                        }
                        }

                        if (!empty($custcontact)) {
                        $formmail->substit['__CONTACTCIVNAME__']=$custcontact;
                        }
                        }*/


                        // Tableau des parametres complementaires du post
                        $formmail->param['action']=$action;
                        $formmail->param['models']=$modelmail;
                        $formmail->param['socid']=$object->id;
                        $formmail->param['returnurl']=$_SERVER["PHP_SELF"].'?socid='.$object->id;

                        // Init list of files
                        if (GETPOST("mode")=='init')
                        {
                                $formmail->clear_attached_files();
                                $formmail->add_attached_files($file,basename($file),dol_mimetype($file));
                        }

                        print $formmail->get_form();

                        print '<br>';
                }
                else
                {

                if (empty($conf->global->SOCIETE_DISABLE_BUILDDOC))
                {
                                print '<div class="fichecenter"><div class="fichehalfleft">';
                    print '<a name="builddoc"></a>'; // ancre

                    /*
                     * Documents generes
                     */
                    $filedir=$conf->societe->multidir_output[$object->entity].'/'.$object->id;
                    $urlsource=$_SERVER["PHP_SELF"]."?socid=".$object->id;
                    $genallowed=$user->rights->societe->creer;
                    $delallowed=$user->rights->societe->supprimer;

                    $var=true;

                    $somethingshown=$formfile->show_documents('company',$object->id,$filedir,$urlsource,$genallowed,$delallowed,'',0,0,0,28,0,'',0,'',$object->name,$object->default_lang);

                                print '</div><div class="fichehalfright"><div class="ficheaddleft">';


                                print '</div></div></div>';

                    print '<br>';
                }

                print '<div class="fichecenter"><br></div>';

                // Subsidiaries list
                $result=show_subsidiaries($conf,$langs,$db,$object);

                // Contacts list
                if (empty($conf->global->SOCIETE_DISABLE_CONTACTS))
                {
                    $result=show_contacts($conf,$langs,$db,$object,$_SERVER["PHP_SELF"].'?socid='.$object->id);
                }

                // Addresses list
                if (! empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT))
                {
                        $result=show_addresses($conf,$langs,$db,$object,$_SERVER["PHP_SELF"].'?socid='.$object->id);
                }

                // Projects list
                $result=show_projects($conf,$langs,$db,$object,$_SERVER["PHP_SELF"].'?socid='.$object->id);
                }
    }

}


// End of page
llxFooter();
$db->close();
