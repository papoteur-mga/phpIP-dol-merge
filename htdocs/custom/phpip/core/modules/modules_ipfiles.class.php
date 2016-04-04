<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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
 * or see http://www.gnu.org/
 */

/**
 *          \file       htdocs/core/modules/societe/modules_ipfiles.class.php
 *              \ingroup    societe
 *              \brief      File with parent class of submodules to get list of files in phpIP
require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';


/**
 *      \class      ModeleThirdPartyFile
 *      \brief      Parent class for third parties files
 */
abstract class ModeleThirdPartyMatter
{
    var $error='';

    /**
     *  Return list of files
     *
     *  @param  DoliDB          $db                                  Database handler
     *  @return array                                                List of files
     */
    static function liste_matter($db,$company)
    {
        global $conf;

        $liste=array();

/*        include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';*/
        $liste=getListOfFiles($db,$company); 

        return $liste;
    }

}

function getListOfFiles($db,$company)
{
    global $conf,$langs;
    $liste=array();
    $found=0;
    $dirtoscan='';

    $sql = "SELECT `ID` as id, `category_code`, `caseref`, `country`, `origin` FROM `matter` m WHERE ";
    $sql.= "(m.`parent_ID` IN (SELECT ml.`matter_ID` FROM `matter_actor_lnk` ml, `actor` a WHERE a.`ID` = ml.`actor_ID` AND a.`display_name` LIKE '".$company."' AND ml.shared=1))";
    $sql.= "OR (m.`container_ID` IN (SELECT ml.`matter_ID` FROM `matter_actor_lnk` ml, `actor` a WHERE a.`ID` = ml.`actor_ID` AND a.`display_name` LIKE '".$company."' AND ml.shared=1))";
    $sql.= "OR (m.`ID` IN (SELECT ml.`matter_ID` FROM `matter_actor_lnk` ml, `actor` a WHERE a.`ID` = ml.`actor_ID` AND a.`display_name` LIKE '".$company."'))";

    dol_syslog('modules_ipfiles.class.php::getListOfFiles', LOG_DEBUG);
    $resql = $db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        $i = 0;
        while ($i < $num)
        {
            $found=1;
            $obj = $db->fetch_object($resql);
            if ( $obj->shared == 1) {   // it's container
            }
            else {               

                $liste[$obj->id]=$obj->category_code.$obj->caseref.$obj->country.$obj->origin;
                $i++;
            }
        }
    }
    else
    {
        dol_print_error($db);
        return -1;
    }

    if ($found) return $liste;
    else return 0;
}
