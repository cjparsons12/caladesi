/*
 * Script
 */

DROP DATABASE splits2;

CREATE DATABASE splits2; 
USE splits2;

CREATE TABLE class (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  code CHAR(3) NOT NULL,
  name CHAR(3) NOT NULL
);

CREATE TABLE league (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  class_id INT NOT NULL,
  name CHAR(3) NOT NULL,
  FOREIGN KEY (class_id) REFERENCES class(id)
);

CREATE TABLE team (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  league_id INT NOT NULL,
  team_abbrev CHAR(10) NOT NULL,
  team_id INT,
  parent_team_abbrev CHAR(5) NOT NULL,
  parent_team_id INT,
  FOREIGN KEY (league_id) REFERENCES league(id)
);

CREATE TABLE game (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  game_id CHAR(30) NOT NULL,
  game_date DATE NOT NULL,
  game_data_directory CHAR(80) NOT NULL,
  away_team_id INT NOT NULL,
  home_team_id INT NOT NULL,
  FOREIGN KEY (away_team_id) REFERENCES team(id),
  FOREIGN KEY (home_team_id) REFERENCES team(id),
  UNIQUE (game_id)
);

CREATE TABLE player (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  player_id INT NOT NULL,
  firstname CHAR(20) NOT NULL, 
  lastname CHAR(20) NOT NULL, 
  position CHAR(10), 
  height CHAR(5), 
  weight INT, 
  bats CHAR(1), 
  throws CHAR(1), 
  dateofbirth DATE,
  UNIQUE (player_id)
);

CREATE TABLE player_game (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  player_id INT NOT NULL,
  game_id INT NOT NULL,
  team_id INT NOT NULL,
  FOREIGN KEY (player_id) REFERENCES player(id),
  FOREIGN KEY (game_id) REFERENCES game(id),
  FOREIGN KEY (team_id) REFERENCES team(id)
);

CREATE TABLE batter_game (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  player_game_id INT NOT NULL,
  position VARCHAR(30) NOT NULL,
  batting_order INT NOT NULL,
  at_bats INT NOT NULL,
  hits INT NOT NULL,
  doubles INT NOT NULL,
  triples INT NOT NULL,
  homeruns INT NOT NULL,
  walks INT NOT NULL,
  strikeouts INT NOT NULL,
  sac INT NOT NULL,
  sac_fly INT NOT NULL,
  hit_by_pitch INT NOT NULL,
  runs INT NOT NULL,
  rbis INT NOT NULL,
  stolen_bases INT NOT NULL,
  caught_stealing INT NOT NULL,
  total_bases INT NOT NULL,
  plate_appearances INT NOT NULL,
  on_base INT NOT NULL,
  FOREIGN KEY (player_game_id) REFERENCES player_game(id)
);

CREATE TABLE pitcher_game (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  player_game_id INT NOT NULL,
  FOREIGN KEY (player_game_id) REFERENCES player_game(id)
);

INSERT INTO class (id, code, name)
VALUES(NULL, "aaa", "AAA");
SET @last_id = LAST_INSERT_ID();
INSERT INTO league (id, class_id, name)
VALUES(NULL, @last_id, "INT");
INSERT INTO league (id, class_id, name)
VALUES(NULL, @last_id, "PCL");

INSERT INTO class (id, code, name)
VALUES(NULL, "aax", "AA");
SET @last_id = LAST_INSERT_ID();
INSERT INTO league (id, class_id, name)
VALUES(NULL, @last_id, "EAS");
INSERT INTO league (id, class_id, name)
VALUES(NULL, @last_id, "SOU");
INSERT INTO league (id, class_id, name)
VALUES(NULL, @last_id, "TEX");

INSERT INTO class (id, code, name)
VALUES(NULL, "afa", "A+");
SET @last_id = LAST_INSERT_ID();
INSERT INTO league (id, class_id, name)
VALUES(NULL, @last_id, "CAL");
INSERT INTO league (id, class_id, name)
VALUES(NULL, @last_id, "CAR");
INSERT INTO league (id, class_id, name)
VALUES(NULL, @last_id, "FSL");

INSERT INTO class (id, code, name)
VALUES(NULL, "afx", "A");
SET @last_id = LAST_INSERT_ID();
INSERT INTO league (id, class_id, name)
VALUES(NULL, @last_id, "MID");
INSERT INTO league (id, class_id, name)
VALUES(NULL, @last_id, "SAL");

INSERT INTO class (id, code, name)
VALUES(NULL, "asx", "A-");
SET @last_id = LAST_INSERT_ID();
INSERT INTO league (id, class_id, name)
VALUES(NULL, @last_id, "NWL");
INSERT INTO league (id, class_id, name)
VALUES(NULL, @last_id, "NYP");

INSERT INTO class (id, code, name)
VALUES(NULL, "rok", "R");
SET @last_id = LAST_INSERT_ID();
INSERT INTO league (id, class_id, name)
VALUES(NULL, @last_id, "APP");
INSERT INTO league (id, class_id, name)
VALUES(NULL, @last_id, "AZL");
INSERT INTO league (id, class_id, name)
VALUES(NULL, @last_id, "DSL");
INSERT INTO league (id, class_id, name)
VALUES(NULL, @last_id, "GCL");
INSERT INTO league (id, class_id, name)
VALUES(NULL, @last_id, "PIO");
