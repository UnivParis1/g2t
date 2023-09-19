#!/bin/bash
chemin=`dirname $0`
cd $chemin
chemin=`pwd`

mydate=`date +%Y-%m-%d`

logfilename="trace_cron_$mydate.log"
if [ -z "$G2T_LOG_PATH" ]
then
   logfile="./log/$logfilename"
else
   logfile="$G2T_LOG_PATH/$logfilename"
fi

echo `date` debut traitement >>$logfile

php php/switch_synchronisation.php actif >>$logfile

numjour=`date +%w`
## Si le numero du jour est different de 0 ou 6 alors on traite les fichiers d'import
## 0 = Dimanche
## 6 = Samedi
if [ $numjour -ne 6 -a $numjour -ne 0 ]
then
   php php/import_agent_xml.php >>$logfile 2>>$logfile
   php php/import_absence_xml.php >>$logfile 2>>$logfile
   php php/import_structure_xml.php >>$logfile 2>>$logfile
   
   nomfichierspecifique="specific/post_structure.php"
   if [ -f "$nomfichierspecifique" ]
   then
      php $nomfichierspecifique >>$logfile 2>>$logfile
   else
      echo "Le fichier $nomfichierspecifique n'existe pas - On l'ignore" >>$logfile 2>>$logfile
   fi
   
   php php/import_affectation_siham_xml.php >>$logfile 2>>$logfile

   nomfichierspecifique="specific/post_affectation.php"
   if [ -f "$nomfichierspecifique" ]
   then
      php $nomfichierspecifique >>$logfile 2>>$logfile
   else
      echo "Le fichier $nomfichierspecifique n'existe pas - On l'ignore" >>$logfile
   fi

   php php/calcul_solde.php >>$logfile 2>>$logfile

   nomfichierspecifique="specific/post_solde.php"
   if [ -f "$nomfichierspecifique" ]
   then
      php $nomfichierspecifique >>$logfile 2>>$logfile
   else
      echo "Le fichier $nomfichierspecifique n'existe pas - On l'ignore" >>$logfile
   fi

   php php/mail_conges.php >>$logfile 2>>$logfile
   php php/mail_declarationTP.php >>$logfile 2>>$logfile
   php php/synchro_demandes_cet.php >>$logfile 2>>$logfile
   php php/synchro_conventions_teletravail.php >>$logfile 2>>$logfile
fi

numjour=`date +%d`

##Si on est le premier jour du mois
if [ $numjour -eq 1 ]
then
   echo "Avant generation solde" >>$logfile
   php php/generer_solde.php >>$logfile 2>>$logfile
   echo "Avant controles post MAJ" >>$logfile
   php php/ctrl_post_maj.php >>$logfile 2>>$logfile
   echo "Avant generation de l'historique des CET" >>$logfile
   php php/demande_cet.php >>$logfile 2>>$logfile
fi
php php/mail_alerte_reliquats.php >>$logfile
php php/mail_alerte_teletravail.php >>$logfile

php php/switch_synchronisation.php inactif >>$logfile

echo `date`  fin de traitement >>$logfile

