Installation du module
======================
Copier les fichiers du module dans htdocs/custom

Activer les modules personnalisés en décommentant les lignes :

> $dolibarr_main_url_root_alt='/custom';
> $dolibarr_main_document_root_alt='/var/www/html/dolibarr-dev/htdocs/custom';

dans htdocs/conf/conf.php.

Activer le module dans Configuration/modules/Modules expérimentaux

Documentation utilisateur
=========================
Le module ajoute un onglet dans la partie *Tiers* de Dolibarr. Cet onglet se nomme "phpIP". Il affiche des informations sur le tiers et une barre de choix de création de documents à partir de modèles ODT.
La liste de modèles est établie à partir du contenu du répertoire htdocs/documents/doctemplates/
La liste "Matter" n'existe que si des dossiers ont été trouvés dans phpIP pour le client choisi comme tiers. La correspondance se fait entre le nom affiché dans Dolibarr et le champ "Display name" de phpIP.

Pour utiliser un modèle, il suffit de créer un document ODT dont le nom commence par "template_" et d'y insérer le texte {matter_variable} aux endroits souhaités. Le même champ peut être utilisé plusieurs fois. Le document "template_matter.odt" fournit la liste des champs qu'il est possible de choisir. Les coordonnées du client proviennent de Dolibarr.

Les dates sont formatées au format français par programmation. 
