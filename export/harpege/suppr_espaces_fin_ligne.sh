#!/bin/ksh

########################

FICHIER=$1
if [ toto$FICHIER = toto ] ; then
   echo "Syntaxe : $0 <nom de fichier>"
   exit 1
fi

sed -r "s/ +$//g" $FICHIER > $FICHIER.tmp


### cat $FICHIER | awk '{ ligne = $0
###                      while (substr(ligne, length(ligne)-10+1, 10) == "          ") { ligne = substr(ligne, 1, length(ligne)-10+1) }
###                      while (substr(ligne, length(ligne), 1) == " ") { ligne = substr(ligne, 1, length(ligne)-1) }
###                      print ligne }' > $FICHIER.tmp

mv $FICHIER.tmp $FICHIER

# fin de script

