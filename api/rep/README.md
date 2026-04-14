# Routes backend commercial

Base URL: `crm/api/rep/`

- `POST login.php`
  - Authentifie un commercial et retourne un token Bearer.
- `GET meta.php`
  - Retourne les villes affectees au commercial et les types de reponse.
- `GET visits.php`
  - Retourne la liste des visites du commercial.
  - Filtres supportes: `city_id`, `response_id`, `period`.
- `POST visits.php`
  - Cree une nouvelle visite pour le commercial connecte.
- `POST visit_update.php`
  - Met a jour une visite appartenant au commercial.
- `POST visit_delete.php`
  - Supprime une visite appartenant au commercial.
- `GET export.php`
  - Exporte les visites du commercial au format CSV.

Headers attendus pour les routes protegees:

- `Authorization: Bearer <token>`
