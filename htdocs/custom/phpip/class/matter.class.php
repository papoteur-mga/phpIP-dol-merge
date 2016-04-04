<?php
/*  *
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
 *	\file       htdocs/societe/class/matter.class.php
 *	\ingroup    societe
 *	\brief      File for matter class
 */
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';


/**
 *	Class to manage phpIP matter
 */
class Matter extends Societe
{
    public $element='matter';
    public $table_element = 'matter';
    public $fk_element='fk_matter';

    var $matter_id;
    var $matter_category_code;
    var $matter_country;
    var $matter_origin;
    var $matter_caseref;

    var $matter_type_code;
    var $matter_idx;
    var $matter_parent_ID;
    var $matter_container_ID;
    var $matter_responsible;
    var $matter_dead;
    var $matter_notes;
    var $matter_expire_date;
    var $matter_category;
    var $matter_filed_date;
    var $matter_filed_nr;
    var $matter_pub_date;
    var $matter_pub_nr;
    var $matter_grant_date;
    var $matter_grant_nr;
    var $matter_inventors;
    var $matter_applicants;
    var $matter_expire;
    var $matter_title;
    var $matter_cli_ref;
    

    /**
     *    Constructor
     *
     *    @param	DoliDB		$db		Database handler
     */
    public function __construct($db, $socid)
    {
        $this->db = $db;
        $this->socid = $socid;
        return 1;
    }


    /**
     *    Load a third party from database into memory
     *
     *    @param	int		$rowid			Id of matter to load
     *    @return   int						>0 if OK, <0 if KO or if two records found for same ref or idprof, 0 if not found.
     */
    function fetch_matter($rowid)
    {
        global $langs;
        global $conf;

         dol_syslog('fetch matter '.$rowid);
         if (empty($rowid) ) return -1;
         
         // pour formater les dates
         setlocale(LC_TIME, "fr_FR.utf8");
         
        // recupere les données de la societe
        $this->fetch($this->socid);
        
        //recupere les donnees du dossier
        $sql = 'SELECT m.ID as matter_id, m.category_code, m.caseref, m.country, m.origin, m.type_code';
        $sql .= ', m.idx, m.parent_ID, m.container_ID, m.responsible, m.dead, m.notes, m.expire_date';
        $sql .= ', mc.category as category';
        $sql .= ' FROM matter as m';
        $sql .= ' LEFT JOIN matter_category as mc ON m.category_code = mc.code';
        $sql .= ' WHERE m.ID = '.$rowid;
        dol_syslog('fetch matter '.$rowid);

        $resql=$this->db->query($sql);
        dol_syslog(get_class($this)."::fetch ".$sql);
        if ($resql)
        {
            $num=$this->db->num_rows($resql);
            if ($num > 1)
            {
                $this->error='Fetch several records found for ref='.$ref;
                dol_syslog($this->error, LOG_ERR);
                $result = -2;
            }
            if ($num == 1)
            {
                $obj = $this->db->fetch_object($resql);
                $this->matter_id= $obj->matter_id;
                $this->matter_category_code= $obj->category_code;
                $this->matter_caseref=$obj->caseref;
                $this->matter_country= $obj->country;
                $this->matter_origin= $obj->origin;
                $this->matter_type_code= $obj->type_code;
                $this->matter_idx= $obj->idx;
                $this->matter_parent_ID= $obj->parent_ID;
                $this->matter_container_ID= $obj->container_ID;
                $this->matter_responsible= $obj->responsible;
                $this->matter_dead= $obj->dead;
                $this->matter_notes= $obj->notes;
                $this->matter_expire_date= $obj->expire_date;
                $this->matter_category = $obj->category;
                

                /*      trouve d'autres donnees */
                // depot

                $sql = 'SELECT e.event_date, e.detail FROM event as e ';
                $sql .= ' WHERE e.code = "FIL" AND e.matter_ID = '.$rowid;
                $resql=$this->db->query($sql);
                dol_syslog(get_class($this)."::fetch ".$sql);
                if ($resql)
                {
                    $num = $this->db->num_rows($resql);
                    if ($num == 1)
                    {
                        $obj = $this->db->fetch_object($resql);
                        $this->matter_filed_date=strftime("%e %B %Y",strtotime($obj->event_date));
                        $this->matter_filed_nr =$obj->detail;
                    }
                }
                
                //  Publication
                $sql = 'SELECT e.event_date, e.detail FROM event as e ';
                $sql .= ' WHERE e.code = "PUB" AND e.matter_ID = '.$rowid;
                $resql=$this->db->query($sql);
                dol_syslog(get_class($this)."::fetch ".$sql);
                if ($resql)
                {
                    $num = $this->db->num_rows($resql);
                    if ($num == 1)
                    {
                        $obj = $this->db->fetch_object($resql);
                        $this->matter_pub_date=strftime("%e %B %Y",strtotime($obj->event_date));
                        $this->matter_pub_nr =$obj->detail;
                    }
                }
                
                // Publication de la delivrance

                $sql = 'SELECT e.event_date, e.detail FROM event as e ';
                $sql .= ' WHERE e.code = "PR" AND e.matter_ID = '.$rowid;
                $resql=$this->db->query($sql);
                dol_syslog(get_class($this)."::fetch ".$sql);
                if ($resql)
                {
                    $num = $this->db->num_rows($resql);
                    if ($num == 1)
                    {
                        $obj = $this->db->fetch_object($resql);
                        $this->matter_grant_date=strftime("%e %B %Y",strtotime($obj->event_date));
                        $this->matter_grant_nr =$obj->detail;
                    }
                }
                // Trouve le titre officiel
                if ($this->matter_container_ID != null) {
                    $upper_matter = $this->matter_container_ID;
                }
                else {
                    $upper_matter = $rowid;
                }
                $sql = 'SELECT c.value FROM classifier as c ';
                $sql .= ' WHERE c.type_code = "TITOF" AND c.matter_ID = '.$upper_matter;
                $resql=$this->db->query($sql);
                if ($resql)
                {
                    $num = $this->db->num_rows($resql);
                    if ($num == 1)
                    {
                        $obj = $this->db->fetch_object($resql);
                        $this->matter_title=$obj->value;
                    }
                }
                
                //  référence du client
                $sql = 'SELECT mal.actor_ref FROM matter_actor_lnk as mal ';
                $sql .= ' WHERE mal.matter_ID = '.$upper_matter." AND mal.role = 'CLI'";
                $resql=$this->db->query($sql);
                dol_syslog(get_class($this)."::fetch ".$sql);
                if ($resql)
                {
                    $num = $this->db->num_rows($resql);
                    dol_syslog(get_class($this)."::fetch ".$sql.' '.$num.'resultats');
                    $i=0;
                    while ($i < $num)
                    {
                        $obj = $this->db->fetch_object($resql);
                        $this->matter_cli_ref=$obj->actor_ref;
                        $i++;
                    }
                    
                }

                /*      trouve les inventeurs */
                $result = 1;
                $sql = 'SELECT i.name, i.first_name FROM matter_actor_lnk as mal ';
                $sql.= 'JOIN actor as i ON mal.actor_ID = i.ID ';
                $sql .= ' WHERE mal.matter_ID = '.$upper_matter." AND mal.role = 'INV'";
                $resql=$this->db->query($sql);
                dol_syslog(get_class($this)."::fetch ".$sql);
                if ($resql)
                {
                    $num = $this->db->num_rows($resql);
                    dol_syslog(get_class($this)."::fetch ".$sql.' '.$num.'resultats');
                    $i=0;
                    while ($i < $num)
                    {
                        if ($i != 0) $this->matter_inventors = $this->matter_inventors.', ';
                        $obj = $this->db->fetch_object($resql);
                        $this->matter_inventors=$this->matter_inventors.$obj->first_name.' '.$obj->name;
                        $i++;
                    }
                    dol_syslog('Inventeurs : '.$this->matter_inventors);
                }

                /*      trouve les deposants */
                $result = 1;
                $sql = 'SELECT i.name, i.first_name FROM matter_actor_lnk as mal ';
                $sql.= 'JOIN actor as i ON mal.actor_ID = i.ID ';
                $sql .= ' WHERE mal.matter_ID = '.$upper_matter." AND mal.role = 'APP'";
                $resql=$this->db->query($sql);
                
                if ($resql)
                {
                    $num = $this->db->num_rows($resql);
                    dol_syslog(get_class($this)."::fetch ".$sql.' '.$num.'resultats');
                    $i=0;
                    while ($i < $num)
                    {
                        if ($i != 0) $this->matter_applicants = $this->matter_applicants.', ';
                        $obj = $this->db->fetch_object($resql);
                        $this->matter_applicants=$this->matter_applicants.$obj->first_name.' '.$obj->name;
                        $i++;
                    }
                     dol_syslog('Déposants : '.$this->matter_applicants);
                }

            }
            else
			{
                $result = 0;
            }

            $this->db->free($resql);
        }
        else
		{
            $this->error=$this->db->lasterror();
            $result = -3;
        }

        // Use first price level if level not defined for third party
        if (! empty($conf->global->PRODUIT_MULTIPRICES) && empty($this->price_level)) $this->price_level=1;

        return $result;
    }

}