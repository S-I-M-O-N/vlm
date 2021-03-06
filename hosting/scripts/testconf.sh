#!/bin/bash
# source d'inspiration : v2.openesub.org / Auteur : paparazzia@gmail.com / Licence : GPL2

#pour tester la configuration
if test "$VLMRACINE" = ""; then
    echo "!!! ERREUR : Veuillez définir la variable d'environnement VLMRACINE"
    echo "!!! HINT : Par exemple dans votre .bashrc"
    exit 1
fi

source $VLMRACINE/conf/conf_script || { echo "ERREUR: votre fichier de configuration n'est pas disponible"; exit 1;}

echo "Création des répertoires si nécessaire"
mkdir -p $VLMJEUROOT
mkdir -p $VLMTEMP
mkdir -p $VLMVLMCPHP
mkdir -p $VLMVLMCSO
mkdir -p $VLMGRIBS
mkdir -p $VLMBIN
mkdir -p $VLMDATAS
mkdir -p $VLMGSHHS
mkdir -p $VLMPOLARS
mkdir -p $VLMCACHE
mkdir -p $VLMLOG

