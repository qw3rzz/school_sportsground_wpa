USE sportoviste_rezervace;

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

CREATE TABLE uzivatele (
                           id          INT AUTO_INCREMENT PRIMARY KEY,
                           jmeno       VARCHAR(50)  NOT NULL,
                           prijmeni    VARCHAR(50)  NOT NULL,
                           email       VARCHAR(100) NOT NULL UNIQUE,
                           heslo_hash  VARCHAR(255) NOT NULL,
                           role        ENUM('student','admin') NOT NULL DEFAULT 'student',
                           vytvoreno   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE sportoviste (
                             id        INT AUTO_INCREMENT PRIMARY KEY,
                             nazev     VARCHAR(100) NOT NULL,
                             popis     TEXT,
                             kapacita  INT NOT NULL DEFAULT 1,
                             aktivni   BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE oteviraci_doby (
                                id             INT AUTO_INCREMENT PRIMARY KEY,
                                sportoviste_id INT NOT NULL,
                                den_tydne      TINYINT NOT NULL,
                                otevreno_od    TIME NOT NULL,
                                otevreno_do    TIME NOT NULL,
                                FOREIGN KEY (sportoviste_id) REFERENCES sportoviste(id) ON DELETE CASCADE
);

CREATE TABLE rezervace (
                           id             INT AUTO_INCREMENT PRIMARY KEY,
                           uzivatel_id    INT NOT NULL,
                           sportoviste_id INT NOT NULL,
                           datum          DATE NOT NULL,
                           cas_od         TIME NOT NULL,
                           cas_do         TIME NOT NULL,
                           stav           ENUM('cekajici','potvrzena','zrusena') NOT NULL DEFAULT 'cekajici',
                           poznamka       TEXT,
                           vytvoreno      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                           FOREIGN KEY (uzivatel_id)    REFERENCES uzivatele(id)   ON DELETE CASCADE,
                           FOREIGN KEY (sportoviste_id) REFERENCES sportoviste(id) ON DELETE CASCADE
);

INSERT INTO sportoviste (nazev, popis, kapacita) VALUES
                                                     ('Tělocvična',      'Hlavní školní tělocvična', 30),
                                                     ('Fitnessko',       'Posilovna s vybavením',    15),
                                                     ('Venkovní hřiště', 'Basketbal a volejbal',     20);