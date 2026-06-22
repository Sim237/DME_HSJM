-- Migration : Hébergement physique dans un service externe
-- Un patient reste administrativement dans son service d'origine
-- mais peut occuper un lit d'un autre service.

ALTER TABLE hospitalisations
    ADD COLUMN service_hebergement_id INT DEFAULT NULL
    COMMENT 'Service physique d''hébergement si différent du service administratif';

ALTER TABLE hospitalisations
    ADD CONSTRAINT fk_hospit_hebergement
    FOREIGN KEY (service_hebergement_id) REFERENCES services(id) ON DELETE SET NULL;
