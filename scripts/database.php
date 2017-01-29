<?php
class Database
{
  private $db;

  public function connect()
  {
    //define('DB_HOST', 'localhost');
    //define('DB_USER', 'root');
    //define('DB_PASSWORD', 'SWF0rge!');
    //define('DB_NAME', 'splits');
    //$this->db = mysqli_connect(DB_HOST,DB_USER,DB_PASSWORD,DB_NAME);
    $this->db = mysqli_connect('localhost','root','SWF0rge!','splits1');
  }

  public function insertGame($game)
  {
    //$insertdate = date("Y-m-d", strtotime($game->date));
    $insertdate = $game->date;
    //echo 'Game Date: ', $insertdate, PHP_EOL;
    $away_db_id = $game->away_team->db_id;
    $home_db_id = $game->home_team->db_id;
    
    $sql = "INSERT INTO game (game_id, game_date, game_data_directory, away_team_id, home_team_id) 
      VALUES ('$game->external_game_id', '$insertdate', '$game->game_data_directory', '$away_db_id', '$home_db_id')";

    $result = mysqli_query($this->db,$sql);
    if(! $result )
    {
      die('Could not enter game: ' . mysqli_error($this->db));
    }
    
    return mysqli_insert_id($this->db);
  }
  
  public function insertTeam($team, $league)
  {
    $league_id = self::getLeagueId($league);

    $sql = "INSERT INTO team (league_id, team_abbrev, team_id, parent_team_abbrev, parent_team_id) 
      VALUES ('$league_id', '$team->team', '$team->team_id', '$team->parent_team', '$team->parent_team_id')";

    $result = mysqli_query($this->db,$sql);
    if(! $result )
    {
      die('Could not enter team: ' . mysqli_error($this->db));
    }
    
    return mysqli_insert_id($this->db);
  }
  
  public function insertPlayer($p)
  {
    $insertdate = date("Y-m-d", strtotime($p->dob));
    //echo 'DOB: ', $insertdate, PHP_EOL;
    $fname = mysqli_real_escape_string($this->db, $p->first_name);
    $lname = mysqli_real_escape_string($this->db, $p->last_name);

    $sql = "INSERT INTO player (player_id, firstname, lastname, position, height, weight, bats, throws, dateofbirth)
      VALUES ('$p->external_player_id', '$fname', '$lname', '$p->position', '$p->height', '$p->weight', '$p->bats', '$p->throws', '$insertdate')";


    $result = mysqli_query($this->db,$sql);
    if(! $result )
    {
      die('Could not enter player: ' . mysqli_error($this->db));
    }
    
    return mysqli_insert_id($this->db);
  }

  public function insertPlayerGame($pid, $gid, $tid)
  {
    $sql = "INSERT INTO player_game (player_id, game_id, team_id)
      VALUES ('$pid', '$gid', '$tid')";

    $result = mysqli_query($this->db,$sql);
    if(! $result )
    {
      die('Could not enter player_game: ' . mysqli_error($this->db));
    }
    
    return mysqli_insert_id($this->db);
  }
  
  public function insertBatterStats($pgid, $s)
  {
    $tb = $s->getTotalBases();
    $pa = $s->getPlateAppearances();
    $ob = $s->getOnBase();
    $sql = "INSERT INTO batter_game (player_game_id, position, batting_order, at_bats, hits, doubles, triples, homeruns, walks, strikeouts, sac, sac_fly, hit_by_pitch, runs, rbis, stolen_bases, caught_stealing, total_bases, plate_appearances, on_base)
      VALUES ('$pgid', '$s->pos', '$s->bo', '$s->ab', '$s->h', '$s->d', '$s->t', '$s->hr', '$s->bb', '$s->so', '$s->sac', '$s->sf', '$s->hbp', '$s->r', '$s->rbi', '$s->sb', '$s->cs', '$tb', '$pa', '$ob')";

    $result = mysqli_query($this->db,$sql);
    if(! $result )
    {
      die('Could not enter batter stats: ' . mysqli_error($this->db));
    }
    
    return mysqli_insert_id($this->db);
  }
  
  public function insertPitcherStats($stats)
  {
    // Add to database
  }

  public function getTeamId($team_id)
  {
    $sql = "SELECT id FROM team WHERE team_id='$team_id'";
    $result = mysqli_query($this->db,$sql);
    if(! $result )
    {
      die('Could not find team: ' . mysqli_error($this->db));
    }

    $row = mysqli_fetch_assoc($result);
    return $row['id'];
  }
  
  public function getPlayerId($external_player_id)
  {
    $sql = "SELECT id FROM player WHERE player_id='$external_player_id'";
    $result = mysqli_query($this->db,$sql);
    if(! $result )
    {
      die('Could not find player: ' . mysqli_error($this->db));
    }

    $row = mysqli_fetch_assoc($result);
    return $row['id'];
  }

  public function getClasses()
  {
    $sql = "SELECT code FROM class";
    $result = mysqli_query($this->db,$sql);
    if(! $result )
    {
      die('Could not get classes: ' . mysqli_error($this->db));
    }

    $classes = array();
    while($row = mysqli_fetch_array($result))
    {
      $classes[] = $row['code'];
    }

    return $classes;
  }
  
  public function getLeagues()
  {
    $sql = "SELECT name FROM league";
    $result = mysqli_query($this->db,$sql);
    if(! $result )
    {
      die('Could not get leagues: ' . mysqli_error($this->db));
    }

    $leagues = array();
    while($row = mysqli_fetch_array($result))
    {
      $leagues[] = $row['name'];
    }

    return $leagues;
  }

  public function getLeagueId($name)
  {
    $sql = "SELECT id FROM league WHERE name='$name'";
    $result = mysqli_query($this->db,$sql);
    if(! $result )
    {
      die('Could not get league: ' . mysqli_error($this->db));
    }

    $row = mysqli_fetch_assoc($result);
    return $row['id'];
  }
/*
  public function updateAverageAges($date)
  {
    $leagues = self::getLeagues();
    foreach($leagues as $league)
    {
      $start_date = date ("Y-m-d", strtotime("-30 days", strtotime($date)));
      $end_date = $date;

      $sql = "
      SELECT 
        ((SUM(TIMESTAMPDIFF(DAY, player.dateofbirth, CURDATE()))/365)/ COUNT(*)) as age
      FROM player 
      INNER JOIN player_game on player.id = player_game.player_id
      INNER JOIN (select distinct id from game where date(game_date) between '$start_date' and '$end_date') as games on player_game.game_id = games.id
      INNER JOIN team on player_game.team_id = team.id 
      INNER JOIN league on team.league_id = league.id 
      WHERE league.name = '$league'
      ";

      //echo 'query string: ', $sql, PHP_EOL;

      $result = mysqli_query($this->db, $sql);
      $row = mysqli_fetch_assoc($result);
      $age = $row['age'];

      //echo $league, ' = ', $age, PHP_EOL;

      $sql = "
      UPDATE league 
        SET average_age='$age' 
      WHERE name='$league'
      ";

      $result = mysqli_query($this->db, $sql);
    }
  }
*/
}

?>
