<?php // $Id: report_customsql.php,v 1.1.2.1 2010/11/16 15:40:22 tjhunt Exp $ 
// report_customsql.php - created with Moodle 1.9.10+ (Build: 20101103) (2007101591)
// creted by Séverin Terrier.


$string['addreport'] = 'Ajouter une nouvelle requête';
$string['anyonewhocanveiwthisreport'] = 'Toute personne pouvant voir ce rapport (report/customsql:view)';
$string['archivedversions'] = 'Versions archivées de cette requête';
$string['automaticallymonthly'] = 'Planifiée, le premier jour de chaque mois';
$string['automaticallyweekly'] = 'Planifiée, le premier jour de chaque semaine';
$string['availablereports'] = 'Requêtes à la demande';
$string['availableto'] = 'Disponible pour $a.';
$string['backtoreportlist'] = 'Retour à la liste des requêtes';
$string['customsql'] = 'Rapports personnalisés';
$string['customsql:definequeries'] = 'Définir des requêtes personnalisées';
$string['customsql:view'] = 'Voir les rapports personnalisés';
$string['deleteareyousure'] = 'Êtes vous certain de vouloir supprimer cette requête ?';
$string['deletethisreport'] = 'Supprimer cette requête';
$string['description'] = 'Description';
$string['displayname'] = 'Nom de la requête';
$string['displaynamerequired'] = 'Vous devez saisir un nom de requête';
$string['displaynamex'] = 'Nom de la requête : $a';
$string['downloadthisreportascsv'] = 'Télécharger le résultat sous forme CSV';
$string['editingareport'] = 'Modifier une requête personnalisée';
$string['editthisreport'] = 'Modifier cette requête';
$string['errordeletingreport'] = 'Erreur de suppression d\'une requête.';
$string['errorinsertingreport'] = 'Erreur d\'insertion d\'une requête.';
$string['errorupdatingreport'] = 'Erreur de modification d\'une requête.';
$string['invalidreportid'] = 'Requête invalide id $a.';
$string['lastexecuted'] = 'Dernière exécution le $a->lastrun. Durée d\'exécution {$a->lastexecutiontime}s.';
$string['manually'] = 'À la demande';
$string['manualnote'] = 'Ces requêtes sont exécutées à la demande, lorsque vous cliquez sur leur lien pour voir les résultats.';
$string['morethanonerowreturned'] = 'Plus d\'une ligne retournée comme résultat. Cette requête devrait retourner une seule ligne.';
$string['nodatareturned'] = 'Cette requête n\'a retourné aucun résultat.';
$string['noexplicitprefix'] = 'Utilisez prefix_ dans votre requête, au lieu de $a.';
$string['noreportsavailable'] = 'Pas de requête disponible';
$string['norowsreturned'] = 'Aucune ligne retournée comme résultat. Cette requête devrait retourner une ligne.';
$string['nosemicolon'] = 'Vous ne pouvez pas utiliser le caractère ; dans la commande SQL.';
$string['notallowedwords'] = 'Vous ne pouvez pas utiliser les mots $a dans la commande SQL.';
$string['note'] = 'Attention';
$string['notrunyet'] = 'Cette requête n\'a pas encore été exécutée.';
$string['onerow'] = 'La requête renvoie une ligne, et cumule les résultats une ligne à la fois';
$string['queryfailed'] = 'Erreur à l\'exécution de la requête : $a';
$string['querynote'] = '<ul>
<li>La chaîne <tt>%%%%WWWROOT%%%%</tt> dans les résultats sera remplacée par <tt>$a</tt>.</li>
<li>Tout champ de sortie ressemblant à une URL sera automatiquement transformé en lien.</li>
<li>La chaîne <tt>%%%%USERID%%%%</tt> dans la requête sera remplacée par le \"user id\" de l\'utilisateur visualisant le rapport, avant l\'exécution du rapport.</li>
<li>Pour des rapports programmés, les chaînes <tt>%%%%STARTTIME%%%%</tt> et <tt>%%%%ENDTIME%%%%</tt> sont remplacées par le timestamp Unix du début et de fin de semaine/mois du rapport dans la requête avant son exécution.</li>
</ul>';
$string['queryrundate'] = 'date d\'exécution de la requête';
$string['querysql'] = 'Requête SQL';
$string['querysqlrequried'] = 'Vous devez saisir du code SQL';
$string['recordlimitreached'] = 'Cette requête a atteint la limite de $a lignes de résultat. Des lignes ont certainement été omises à la fin.';
$string['reportfor'] = 'Requête exécutée le $a';
$string['runable'] = 'Exécution';
$string['runablex'] = 'Exécution : $a.';
$string['schedulednote'] = 'Ces requêtes sont lancées automatiquement le premier jour de chaque semaine ou chaque mois, pour des rapports sur la semaine ou le mois précédent. Ces liens vous permettent de visualiser les résultats qui ont déjà été accumulés.';
$string['scheduledqueries'] = 'Requêtes programmées';
$string['typeofresult'] = 'Type de résultat';
$string['unknowndownloadfile'] = 'Fichier à télécharger inconnu.';
$string['userswhocanconfig'] = 'Administrateurs uniquement (moodle/site:config)';
$string['userswhocanviewsitereports'] = 'Utilisateurs pouvant voir les rapports (moodle/site:viewreports)';
$string['whocanaccess'] = 'Qui peut accéder à cette requête';
