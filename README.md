# Marché Bétail - Plateforme de vente de bétail en ligne

Une plateforme web moderne permettant aux vendeurs de publier des annonces de bétail et aux acheteurs de les consulter et d'effectuer des achats en toute sécurité.

## Fonctionnalités

- 🐮 Publication d'annonces de bétail (bovins, ovins, caprins)
- 🔍 Recherche et filtrage des annonces
- 🛒 Système de panier d'achat
- 💳 Paiement sécurisé
- ⭐ Système d'avis et de notation des vendeurs
- 📱 Interface responsive (mobile-friendly)

## Prérequis

- PHP 8.0 ou supérieur
- MySQL 5.7 ou supérieur
- Apache 2.4 ou supérieur
- Composer (pour les dépendances)
- Extension PHP :
  - mysqli
  - gd
  - fileinfo
  - json

## Installation

1. Clonez le dépôt :

```bash
git clone https://github.com/votre-username/marche-betail.git
cd marche-betail
```

2. Configurez votre serveur web (Apache) :

```apache
<VirtualHost *:80>
    ServerName marche-betail.local
    DocumentRoot /chemin/vers/marche-betail

    <Directory /chemin/vers/marche-betail>
        Options Indexes FollowSymLinks MultiViews
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

3. Créez la base de données :

```bash
mysql -u root -p < sql/betail_db.sql
```

4. Configurez les paramètres de l'application :

- Copiez le fichier de configuration exemple :

```bash
cp includes/config.example.php includes/config.php
```

- Modifiez les paramètres dans `includes/config.php` :
  - Informations de connexion à la base de données
  - URL du site
  - Configuration des emails
  - Clés API pour les paiements

5. Créez les dossiers nécessaires et définissez les permissions :

```bash
mkdir -p uploads/betail
chmod -R 777 uploads
```

## Structure du projet

```
marche-betail/
├── api/                # Endpoints API
├── assets/            # Fichiers statiques (CSS, JS, images)
├── includes/          # Fichiers d'inclusion PHP
├── sql/              # Scripts SQL
├── uploads/          # Dossier pour les uploads
└── vendor/           # Dépendances
```

## Configuration de la base de données

La base de données est automatiquement créée avec le script SQL fourni. Elle contient les tables suivantes :

- `users` : Utilisateurs (acheteurs et vendeurs)
- `betail` : Annonces de bétail
- `commandes` : Commandes des acheteurs
- `avis` : Avis sur les vendeurs

## Utilisation

1. Créez un compte vendeur ou acheteur
2. Pour les vendeurs :
   - Publiez vos annonces avec photos
   - Gérez vos annonces dans le tableau de bord
3. Pour les acheteurs :
   - Parcourez les annonces
   - Ajoutez au panier
   - Effectuez vos achats
   - Laissez des avis

## Sécurité

- Toutes les entrées utilisateur sont nettoyées
- Protection contre les injections SQL
- Validation des uploads de fichiers
- Sessions sécurisées
- Mots de passe hashés

## Contribution

1. Fork le projet
2. Créez votre branche (`git checkout -b feature/AmazingFeature`)
3. Committez vos changements (`git commit -m 'Add some AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrez une Pull Request

## Support

Pour toute question ou problème, veuillez ouvrir une issue sur GitHub.

## Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.
