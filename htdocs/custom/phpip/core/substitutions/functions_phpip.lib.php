<?php

/**             Function called to complete substitution array (before generating on ODT, or a personalized email)
 *              functions xxx_completesubstitutionarray are called by make_substitutions() if file
 *              is inside directory htdocs/core/substitutions
 * 
 *              @param  array           $substitutionarray      Array with substitution key=>val
 *              @param  Translate       $langs                  Output langs
 *              @param  Object          $object                 Object to use to get values
 *              @return void                                    The entry parameter $substitutionarray is modified
 */
function phpip_completesubstitutionarray(&$substitutionarray,$langs,$matter)
{
        global $langs;
        global $conf;
        global $db;

        if (empty($matter->id) ) return -1;




                $substitutionarray['matter_id']=$matter->matter_id ;
                $substitutionarray['matter_category_code']=$matter->matter_category_code ;
                $substitutionarray['matter_country']=$matter->matter_country ;
                $substitutionarray['matter_caseref']=$matter->matter_caseref ;
                $substitutionarray['matter_origin']=$matter->matter_origin ;
                $substitutionarray['matter_type_code']=$matter->matter_type_code ;
                $substitutionarray['matter_idx']=$matter->matter_idx ;
                $substitutionarray['matter_parent_ID']=$matter->matter_parent_ID ;
                $substitutionarray['matter_container_ID']=$matter->matter_container_ID ;
                $substitutionarray['matter_responsible']=$matter->matter_responsible ;
                $substitutionarray['matter_dead']=$matter->matter_dead ;
                $substitutionarray['matter_notes']=$matter->matter_notes ;
                $substitutionarray['matter_expire_date']=$matter->matter_expire_date ;
                $substitutionarray['matter_category']=$matter->matter_category ;
                $substitutionarray['matter_filed_date']=$matter->matter_filed_date ;
                $substitutionarray['matter_filed_nr']=$matter->matter_filed_nr ;
                $substitutionarray['matter_pub_date']=$matter->matter_pub_date ;
                $substitutionarray['matter_pub_nr']=$matter->matter_pub_nr ;
                $substitutionarray['matter_grant_date']=$matter->matter_grant_date ;
                $substitutionarray['matter_grant_nr']=$matter->matter_grant_nr ;
                $substitutionarray['matter_expire_date']=$matter->matter_expire_date ;
                $substitutionarray['matter_inventors']=$matter->matter_inventors;
                $substitutionarray['matter_applicants']=$matter->matter_applicants;
                $substitutionarray['matter_title']=$matter->matter_title;
                $substitutionarray['matter_cli_ref']=$matter->matter_cli_ref;
                
                dol_syslog('Substitution inventeurs '.$substitutionarray['matter_inventors']);
        
    }
/**             Function called to complete substitution array for lines (before generating on ODT, or a personalized email)
 *              functions xxx_completesubstitutionarray_lines are called by make_substitutions() if file
 *              is inside directory htdocs/core/substitutions
 * 
 *              @param  array           $substitutionarray      Array with substitution key=>val
 *              @param  Translate       $langs                  Output langs
 *              @param  Object          $object                 Object to use to get values
 *              @param  Object          $line                   Current line being processed, use this object to get values
 *              @return void                                    The entry parameter $substitutionarray is modified
 */

    function phpip_completesubstitutionarray_lines(&$substitutionarray,$langs,$matter,$line) {
        global $conf,$db;
 
        $myvalue=$matter->matter_inventors[$line]['display_name'];
        $substitutionarray['inventor']=$myvalue;
        dol_syslog('Substitution de ligne inventeur '.$myvalue);
    }
    
    function applicants_completesubstitutionarray_applicants(&$substitutionarray,$langs,$matter,$line) {
        global $conf,$db;
 
        $myvalue=$matter->matter_applicants[$line]['display_name'];
        $substitutionarray['applicant']=$myvalue;
        dol_syslog('Substitution de ligne deposant '.$myvalue);
    }
