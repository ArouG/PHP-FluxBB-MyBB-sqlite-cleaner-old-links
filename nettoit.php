<?php                  
/**************************************************
 *  nettoit.php - objectif = filtre les messages de notre base SQLITE3 pour MyBB et remplace les anciens liens user, forum, topic et post par des 'bons liens'
 *              conditions préalables : l'ancienne base FluxBB, sqlite2, sera préalablement updragée en sqlite3 et renommée en old.sqlite,
 *                                      ses tables ne comporteront aucun préfixe, 
 *                                      la base MyBB sera renommée en base.sqlite et le préfixe sera passé en paramètre par ?p=<pref> 
 *               
 *  1 - Sorry for my english spoken,
 *  2 - the purpose of nettoit.php is to 'clean' an MyBB SQLITE 3 database, named base.sqlite, of the old user, links, forum and topic
 *  3 - the old FluxBB database must by upgrated V3 and renamed old.sqlite (of course !) and placed in the same directory than this file !
 *  4 - the MyBB SQLITE3 database must be renamed base.sqlite and located too in the same directory than this file !
 *  6 - this file is an utf8 and the prefix of MyBB tables will be passed by parameter ?p=<pref>
 *  
 *              Auteur : françois DANTGNY 04/10/2014 
 **************************************************/    
ignore_user_abort(TRUE);
error_reporting(E_ERROR | E_WARNING | E_PARSE); 
set_time_limit(0);    
ini_set("memory_limit" , -1);         
echo '<meta http-equiv="Content-type" content="text/html; charset=utf-8" />';

$out = NEW PDO('sqlite:base.sqlite');    
if ($out){                                                    
    $out->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
    $bid=$out->exec("BEGIN TRANSACTION"); 
    $old = NEW PDO('sqlite:old.sqlite');      
    
    // récupération préfixe nouvelle base MyBB   
    $pref='';
    if ( isset($_GET['p'])){
        $pref=$_GET['p'];
    } 
    if ($old){       
        $old->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING ); 
         
        // récupération anciens id / username vieille base       
        $sqloldus="SELECT id,username FROM users";
        $stmi1=$old->prepare($sqloldus);
        $stmi1->execute();
        $oldusers=$stmi1->fetchAll(PDO::FETCH_ASSOC);  
        
        // on attrape ceux de la nouvelle base
        $sqlous="SELECT uid,username FROM ".$pref."users";
        $stous=$out->prepare($sqlous);
        $stous->execute();
        $outsers=$stous->fetchAll(PDO::FETCH_ASSOC); 
        
        //  outid : pseudo => id
        $outid=array();
        for ($i=0; $i<count($outsers); $i++){
            $outid[$outsers[$i]['username']]=$outsers[$i]['uid'];
        }       
        $nid=array_keys($outid);                                                // liste des nouveaux pseudos
        $OldOutIdUs=array();                                                    // construit table OldOutIdUs  [old Iduser] = out Iduser
        for ($j=0; $j<count($oldusers); $j++){
            if (in_array($oldusers[$j]['username'],$nid)){
                $OldOutIdUs[$oldusers[$j]['id']]=$outid[$oldusers[$j]['username']];   
            } else {    
                $OldOutIdUs[$oldusers[$j]['id']]=0;                             // assigne valeur 0 si pas d'équivalent dans la table MyBB (ex = guest)
            }
        }          
        $tabId=array_keys($OldOutIdUs);     
        unset($outsers);        
        unset($oldusers);
        unset($nid);
        
        // FORUMS
        $sqlforold="SELECT id, forum_name FROM forums";   
        $stmi1=$old->prepare($sqlforold);
        $stmi1->execute();
        $oldforums=$stmi1->fetchAll(PDO::FETCH_ASSOC);                           // anciens forums  
        
        $sqlforout="SELECT fid, name FROM ".$pref."forums WHERE type='f'";      // les <vrais forums>
        $stmi1=$out->prepare($sqlforout);
        $stmi1->execute();
        $outforums=$stmi1->fetchAll(PDO::FETCH_ASSOC);                          // nouveaux forums
        
        // suppose qu'ils ont tous été passés de l'ancienne vers la nouvelle
        $OldIdNameFor=array();
        $OutNameIdFor=array();
        $OldOutIdFor=array();
        for ($j=0; $j<count($oldforums); $j++) $OldIdNameFor[$oldforums[$j]['id']]=$oldforums[$j]['forum_name'];
        for ($j=0; $j<count($outforums); $j++) $OutNameIdFor[$outforums[$j]['name']]=$outforums[$j]['fid'];             
        $OutForNames=array_keys($OutNameIdFor);                                 // liste des noms des nouveaux forums
        for ($j=0; $j<count($oldforums); $j++) {
            if (in_array($oldforums[$j]['forum_name'],$OutForNames)){
                $OldOutIdFor[$oldforums[$j]['id']]=(int)$OutNameIdFor[$oldforums[$j]['forum_name']];
            } else {            
                $OldOutIdFor[$oldforums[$j]['id']]=0;
            }
        }
        unset($oldforums);
        unset($outforums);
        unset($OldIdNameFor);    
        unset($OutNameIdFor);    
        unset($OutForNames);   
        $tabFor=array_keys($OldOutIdFor); 
        
        //var_dump($OldOutIdFor);
        
        // Topics - Threads       
        $sqltopold="SELECT id, subject, posted FROM topics";   
        $stmi1=$old->prepare($sqltopold);
        $stmi1->execute();
        $oldtopics=$stmi1->fetchAll(PDO::FETCH_ASSOC);                          // anciens topics  
        
        $sqltopout="SELECT tid, subject, dateline FROM ".$pref."threads";       // les nouveaux topics
        $stmi1=$out->prepare($sqltopout);
        $stmi1->execute();
        $outtopics=$stmi1->fetchAll(PDO::FETCH_ASSOC);                          // nouveaux forums
        
        // suppose qu'ils ont tous été passés de l'ancienne vers la nouvelle
        $OldOutIdTop=array();
        for ($j=0; $j<count($oldtopics); $j++) {
            $t=-1;
            for ($k=0; $k<count($outtopics); $k++){
                if (($oldtopics[$j]['subject'] == $outtopics[$k]['subject']) && ($oldtopics[$j]['posted'] == $outtopics[$k]['dateline'])){
                    $t=$k;
                    break;    
                }
            }
            if ($t != -1){
                $OldOutIdTop[$oldtopics[$j]['id']]=(int)$outtopics[$t]['tid'];
            } else {            
                $OldOutIdTop[$oldtopics[$j]['id']]=0;
            }
        }
        unset($oldtopics);
        unset($outtopics);  
        $tabTop=array_keys($OldOutIdTop); 
        
        // Posts       
        $sqlpostold="SELECT id, poster_id, posted FROM posts";   
        $stmi1=$old->prepare($sqlpostold);
        $stmi1->execute();
        $oldposts=$stmi1->fetchAll(PDO::FETCH_ASSOC);                          // anciens posts  
        
        $sqlpostout="SELECT pid, uid, dateline FROM ".$pref."posts";       // les nouveaux posts
        $stmi1=$out->prepare($sqlpostout);
        $stmi1->execute();
        $outposts=$stmi1->fetchAll(PDO::FETCH_ASSOC);                          // nouveaux posts
        
        // suppose qu'ils ont tous été passés de l'ancienne vers la nouvelle
        $OldOutIdPost=array();
        for ($j=0; $j<count($oldposts); $j++) {
            $t=-1;
            for ($k=0; $k<count($outposts); $k++){
                if (($OldOutIdUs[$oldposts[$j]['poster_id']] == $outposts[$k]['uid']) && ($oldposts[$j]['posted'] == $outposts[$k]['dateline'])){
                    $t=$k;
                    break;    
                }
            }
            if ($t != -1){
                $OldOutIdPost[$oldposts[$j]['id']]=(int)$outposts[$t]['pid'];
            } else {            
                $OldOutIdPost[$oldposts[$j]['id']]=0;
            }
        }
        unset($oldposts);
        unset($outposts);    
        $tabPost=array_keys($OldOutIdPost); 
        
        /******************************
         *  On commence le nettoyage dans la nouvelle base. On peut maintenant fermer l'ancienne
         */
         
        $old=null;
        
        $sqlmess="SELECT pid, message FROM ".$pref."posts";                     // les nouveaux posts
        $stmi1=$out->prepare($sqlmess);
        $stmi1->execute();
        $mess=$stmi1->fetchAll(PDO::FETCH_ASSOC);                               // grosse table des messages !  
        
        $sqlparent="SELECT pid, tid FROM ".$pref."posts";                       // sélectionne pid ET tid
        $stmi1=$out->prepare($sqlparent);
        $stmi1->execute();
        $pidtid=$stmi1->fetchAll(PDO::FETCH_ASSOC);                 
           
        // création de la table des parents pour l'affichage des posts 
        $parent=array();                            
        for ($i=0; $i<count($pidtid); $i++) $parent[$pidtid[$i]['pid']]=(int)$pidtid[$i]['tid'];
        unset($pidtid);
        
        $sqlup="UPDATE ".$pref."posts SET message=? WHERE pid=?";      
        $stmtup=$out->prepare($sqlup);
        
        echo "La base sqlite comporte ".count($mess)." messages<br>";
        
        $nr=0; $nl=0;
        for ($i=0; $i<count($mess); $i++){
            $message=$mess[$i]['message'];
            $oldmess=$message; $newmess='';
            $cnt=preg_match_all("#\[user=([0-9]+)\](.*)\[\/user\]#U", $message, $tab);
            if ($cnt>0){   
                for ($k=0; $k<count($tab[0]); $k++){
                    if (in_array($tab[1][$k],$tabId)){
                        $newId=$OldOutIdUs[$tab[1][$k]];                             // http://aroug.eu/myBB_1.8/member.php?action=profile&uid=38
                        $newCh="[url=http://aroug.eu/myBB_1.8/member.php?action=profile&uid=".$newId."]".$tab[2][$k]."[/url]"; 
                        $message=str_replace($tab[0][$k],$newCh,$message);  
                        $nr +=1;
                    }
                }
            }       
            $cnt=preg_match_all("#\[forum=([0-9]+)\](.*)\[\/forum\]#U", $message, $tab);  
            if ($cnt>0){                                  
                for ($k=0; $k<count($tab[0]); $k++){
                    if (in_array($tab[1][$k],$tabFor)){
                        $newId=$OldOutIdFor[$tab[1][$k]];                             // http://aroug.eu/myBB_1.8/forumdisplay.php?fid=4
                        $newCh="[url=http://aroug.eu/myBB_1.8/forumdisplay.php?fid=".$newId."]".$tab[2][$k]."[/url]";  
                        $message=str_replace($tab[0][$k],$newCh,$message);   
                        $nr +=1;
                    }
                }
            }       
            $cnt=preg_match_all("#\[topic=([0-9]+)\](.*)\[\/topic\]#U", $message, $tab);           // topics  
            if ($cnt>0){                            
                for ($k=0; $k<count($tab[0]); $k++){
                    if (in_array($tab[1][$k],$tabTop)){
                        $newId=$OldOutIdTop[$tab[1][$k]];                             // http://aroug.eu/myBB_1.8/showthread.php?tid=349
                        $newCh="[url=http://aroug.eu/myBB_1.8/showthread.php?tid=".$newId."]".$tab[2][$k]."[/url]";   
                        $message=str_replace($tab[0][$k],$newCh,$message); 
                        $nr +=1;     
                    }
                }
            }        
            $cnt=preg_match_all("#\[post=([0-9]+)\](.*)\[\/post\]#U", $message, $tab);           // posts
            if ($cnt>0){                       
                for ($k=0; $k<count($tab[0]); $k++){
                    if (in_array($tab[1][$k],$tabPost)){
                        $newId=$OldOutIdPost[$tab[1][$k]];                             // http://aroug.eu/myBB_1.8/showthread.php?tid=6&pid=5931#pid5931
                        $IdTop=$parent[$tab[1][$k]];
                        $newCh="[url=http://aroug.eu/myBB_1.8/showthread.php?tid=".$IdTop."&pid=".$newId."#".$newId."]".$tab[2][$k]."[/url]";  
                        $message=str_replace($tab[0][$k],$newCh,$message); 
                        $nr +=1;
                    }
                }
            }
            $newmess=$message;
            
            if ($oldmess != $newmess){
                  $stmtup->execute(array($message,$mess[$i]['pid']));
                  $nl += 1;
            }  
        }                        
    } 
    echo $nl." messages ont été nettoyés et ".$nr." remplacements ont été réalisés.";
    $bid=$out->exec("COMMIT"); 
    $bid=$out->exec("VACUUM"); 
    $out=null;
}
?>
