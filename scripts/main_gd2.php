<?php
include "database.php";

class Game
{
  public $external_game_id;
  public $league;
  public $game_data_directory;
  public $date;
  public $status;
  public $away_team;
  public $home_team;

  function __construct($game)
  {
    $this->external_game_id = $game['id'];
    $this->league = $game['league'];
    $this->date = date('Y-m-d', strtotime($game['original_date']));
    //$resume_date = $game['resume_date'];
    //if($resume_date != '')
    //  $this->date = date('Y-m-d', strtotime($resume_date));
    
    $this->game_data_directory = $game['game_data_directory'];
    $this->status = $game->status['status'];
    //echo $this->game_id, " ", $this->league, ' ', $this->game_data_directory, ' ', $this->status, PHP_EOL;
  }
}

class Team
{
  public $team;
  public $team_id;
  public $parent_team;
  public $parent_team_id;
  public $db_id;

  function __construct($team)
  {
    $this->team = $team->player[0]['team_abbrev'];
    $this->team_id = $team->player[0]['team_id'];
    $this->parent_team = $team->player[0]['parent_team_abbrev'];
    $this->parent_team_id = $team->player[0]['parent_team_id'];
    //echo $this->team, ' ', $this->parent_team, PHP_EOL;
  }
}

class PlayerStats
{
  public $external_player_id;
  public $external_game_id;
  public $team;
  
  function __construct($stats)
  {
    $this->external_player_id = $stats['id'];
  }
}

class BatterStats extends PlayerStats
{
  public $pos;
  public $bo;
  public $ab;
  public $h;
  public $d;
  public $t;
  public $hr;
  public $bb;
  public $so;
  public $sac;
  public $sf;
  public $hbp;
  public $r;
  public $rbi;
  public $sb;
  public $cs;

  function __construct($stats)
  {
    parent::__construct($stats);
    
    $this->pos = $stats['pos'];
    $this->bo = $stats['bo'];
    $this->ab = $stats['ab'];
    $this->h = $stats['h'];
    $this->d = $stats['d'];
    $this->t = $stats['t'];
    $this->hr = $stats['hr'];
    $this->bb = $stats['bb'];
    $this->so = $stats['so'];
    $this->sac = $stats['sac'];
    $this->sf = $stats['sf'];
    $this->hbp = $stats['hbp'];
    $this->r = $stats['r'];
    $this->rbi = $stats['rbi'];
    $this->sb = $stats['sb'];
    $this->cs = $stats['cs'];
  }

  function getTotalBases()
  {
    return $this->h + $this->d + (2 * $this->t) + (3 * $this->hr);
  }
  
  function getPlateAppearances()
  {
    return $this->ab + $this->bb + $this->hbp + $this->sf;
  }
  
  function getOnBase()
  {
    return $this->h + $this->bb + $this->hbp;
  }
}

class PitcherStats extends PlayerStats
{
  public $start;
  public $out;
  public $bf;
  public $er;
  public $r;
  public $h;
  public $so;
  public $hr;
  public $bb;
  public $np;
  public $s;
  public $gs;

  function __construct($stats, $start)
  {
    parent::__construct($stats);

    $this->start = $start;
    $this->out = $stats['out'];
    $this->bf = $stats['bf'];
    $this->er = $stats['er'];
    $this->r = $stats['r'];
    $this->h = $stats['h'];
    $this->so = $stats['so'];
    $this->hr = $stats['hr'];
    $this->bb = $stats['bb'];
    $this->np = $stats['np'];
    $this->s = $stats['s'];
    $this->gs = $stats['game_score'];
  }
}

class Player
{
  public $external_player_id;
  public $first_name;
  public $last_name;
  public $position;
  public $height;
  public $weight;
  public $bats;
  public $throws;
  public $dob;

  function __construct($player)
  {
    $this->external_player_id = $player['id'];
    $this->first_name = $player['first_name'];
    $this->last_name = $player['last_name'];
    //$this->position = $player['pos']; // some batters list as P
    $this->position = $player['current_position'];
    $this->height = $player['height'];
    $this->weight = $player['weight'];
    $this->bats = $player['bats'];
    $this->throws = $player['throws'];
    $this->dob = $player['dob'];
  }
}

class Gameday2Data
{
  const BASE_URL = "http://gd2.mlb.com";

  public static function getBoxscoreUrl($game) { return self::BASE_URL . $game->game_data_directory . '/' . 'boxscore.xml'; }
  public static function getPlayersUrl($game) { return self::BASE_URL . $game->game_data_directory . '/' . 'players.xml'; }
  public static function getBatterUrl($game, $id) { return self::BASE_URL . $game->game_data_directory . '/' . 'batters' . '/' . $id . '.xml'; }
  public static function getPitcherUrl($game, $id) { return self::BASE_URL . $game->game_data_directory . '/' . 'pitchers' . '/' . $id . '.xml'; }

  public static function getMasterScoreboard($level, $year, $month, $day)
  {
    $ms = sprintf(self::BASE_URL . '/components/game/%4$s/year_%1$04d/month_%2$02d/day_%3$02d/master_scoreboard.xml', $year, $month, $day, $level);
    //echo $ms;
    return file_get_contents($ms);
  }

  private static function getTeamId($db, $team, $league)
  {
    $team_id = $db->getTeamId($team->team_id);
    if($team_id == null)
    {
      $team_id = $db->insertTeam($team, $league);
    }
    return $team_id;
  }

  public static function loadGames($db, $class, $year, $month, $day)
  {
    $gamesXmlFile = self::getMasterScoreboard($class, $year, $month, $day);
    if(! $gamesXmlFile)
    {
      echo 'master_scoreboard.xml for ', $class, ' ', $year, '-', $month, '-', $day, ' NOT FOUND', PHP_EOL;
      return;
    }

    $gamesXml = new SimpleXmlElement($gamesXmlFile);

    foreach($gamesXml->game as $gameXml)
    {
      $game = new Game($gameXml);
      if($game->status == 'Final' && $game->league != 'MEX')
      {
        echo 'Loading ', $game->external_game_id, '...', PHP_EOL;

        $playersXmlFile = file_get_contents(self::getPlayersUrl($game));
        if(! $playersXmlFile)
        {
          echo self::getPlayersUrl($game), ' NOT FOUND!', PHP_EOL;
          continue;
        }
        $playersXml = new SimpleXmlElement($playersXmlFile);

        $game->away_team = new Team($playersXml->team[0]);
        $game->home_team = new Team($playersXml->team[1]);

        $away_team_id = self::getTeamId($db, $game->away_team, $game->league);
        $game->away_team->db_id = $away_team_id;
        $home_team_id = self::getTeamId($db, $game->home_team, $game->league);
        $game->home_team->db_id = $home_team_id;
        echo 'Away: ', $game->away_team->team, '(', $game->away_team->db_id, ')';
        echo 'Home: ', $game->home_team->team, '(', $game->home_team->db_id, ')';
        echo PHP_EOL;

        $game_id = $db->insertGame($game); 
     
        $boxscoreXmlFile = file_get_contents(self::getBoxscoreUrl($game));
        if(! $boxscoreXmlFile)
        {
          echo self::getBoxscoreUrl($game), ' NOT FOUND!', PHP_EOL;
          continue;
        }

        $boxscoreXml = new SimpleXmlElement($boxscoreXmlFile);
        
        $playerStatsArray = self::loadPlayerStats($boxscoreXml, $game);
        foreach($playerStatsArray as $playerStats)
        {
          $player_id = $db->getPlayerId($playerStats->external_player_id);
          if($player_id == null)
          {
            if(is_a($playerStats, "BatterStats"))
              $playerUrl = self::getBatterUrl($game, $playerStats->external_player_id);
            else
              $playerUrl = self::getPitcherUrl($game, $playerStats->external_player_id);
            $playerXmlFile = file_get_contents($playerUrl);
            if(! $playerXmlFile)
            {
              echo $playerUrl, ' NOT FOUND!', PHP_EOL;
              continue;
            }

            $playerXml = new SimpleXmlElement($playerXmlFile);
            $player = new Player($playerXml);

            // add to database
            $player_id = $db->insertPlayer($player);
          }
          echo "Player: ", $player_id, PHP_EOL;

          if($playerStats->team->team_id == $game->away_team->team_id)
            $team_id = $away_team_id; 
          else
            $team_id = $home_team_id; 

          echo "Team: ", $team_id, PHP_EOL;

          $player_game_id = $db->insertPlayerGame($player_id, $game_id, $team_id);

          //echo "Player Stats: ", $playerStats->external_player_id, ',';
          if(is_a($playerStats, "BatterStats"))
          {
            //echo $playerStats->pos, PHP_EOL;
            $db->insertBatterStats($player_game_id, $playerStats);
          }
          else
          {
            //echo $playerStats->so, PHP_EOL;
            //Database::insertPitcherStats($playerStats, $player_id, $game_id);
          }
        }
      }
    }
  }

  public static function loadPlayerStats($boxscore, $game)
  {
    $playerStats = array();

    $i = 0;
    foreach($boxscore->pitching[0]->pitcher as $pitcher)
    {
      $start = false;
      if($i == 0)
        $start = true;
      $stats = new PitcherStats($pitcher, $start);
      $stats->external_game_id = $game->external_game_id;
      $stats->team = $game->away_team;
      $playerStats[] = $stats;
    }
    
    foreach($boxscore->batting[0]->batter as $batter)
    {
      $stats = new BatterStats($batter);
      $stats->external_game_id = $game->external_game_id;
      $stats->team = $game->home_team;
      $playerStats[] = $stats;
    }
    
    $i = 0;
    foreach($boxscore->pitching[1]->pitcher as $pitcher)
    {
      $start = false;
      if($i == 0)
        $start = true;
      $stats = new PitcherStats($pitcher, $start);
      $stats->external_game_id = $game->external_game_id;
      $stats->team = $game->home_team;
      $playerStats[] = $stats;
    }
    
    foreach($boxscore->batting[1]->batter as $batter)
    {
      $stats = new BatterStats($batter);
      $stats->external_game_id = $game->external_game_id;
      $stats->team = $game->away_team;
      $playerStats[] = $stats;
    }

    return $playerStats;
  }
}

function loadDays($start_date, $end_date)
{
  date_default_timezone_set('UTC');
  
  $db = new Database();
  $db->connect();

  //$leagues = array("aaa", "aax", "afa", "afx", "asx", "rok");
  $classes = $db->getClasses();

  while (strtotime($start_date) <= strtotime($end_date))
  {
    $timestamp = strtotime($start_date);
    $day = date('d', $timestamp);
    $month = date('m', $timestamp);
    $year = date('Y', $timestamp);

    foreach($classes as $class)
    {
      Gameday2Data::loadGames($db, $class, $year, $month, $day);
    }

    $start_date = date ("Y-m-d", strtotime("+1 days", strtotime($start_date)));
  }


}

//loadDays('2016-04-07', '2016-04-10');
loadDays('2016-05-31', '2016-07-31');
?>
