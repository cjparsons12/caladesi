<?php    
  
  if (isset($_GET['submit']))
  {
    $days = $_GET['days'];
    $start = date("Y-m-d", strtotime($_GET['startdate']));
    $end = date("Y-m-d", strtotime($_GET['enddate']));
    $league = $_GET['league'];
    $team = $_GET['team'];
    $pa = $_GET['pa'];
    $page = $_GET['page'];
    echo '<!--', http_build_query($_GET), "-->";
  }
  else
  {
    $days = 'Custom';
    $start = '2016-04-01';
    $end = '2016-09-30';
    $league = 'ALL';
    $team = 'ALL';
    $pa = 100;
    $page = 1;
  }

  function getStats($start_date, $end_date, $league, $team, $pa, $page)
  {
    include("../resources/config.php");

    $sql_age = "
    SELECT
     ((SUM(TIMESTAMPDIFF(DAY, player.dateofbirth, '$end_date'))/365)/ COUNT(*)) as age,
     league.id as lid
    FROM player
    INNER JOIN player_game on player.id = player_game.player_id
    INNER JOIN (select distinct id from game where date(game_date) between '$start_date' and '$end_date') as games on player_game.game_id = games.id
    INNER JOIN team on player_game.team_id = team.id
    INNER JOIN league on team.league_id = league.id
    GROUP BY league.id
    ";

    $sql_select = "
    SELECT
      player.firstname, 
      player.lastname, 
      player.position,
      league.name AS league,
      class.name AS class,
      team.parent_team_abbrev AS team,
      (TIMESTAMPDIFF(DAY, player.dateofbirth, '$end_date') / 365) AS age,
      (TIMESTAMPDIFF(DAY, player.dateofbirth, '$end_date') / 365) - ages.age AS age_diff,
      SUM(batter_game.homeruns + batter_game.triples + batter_game.doubles) AS xbh,
      SUM(batter_game.homeruns) as hr,
      SUM(batter_game.stolen_bases) as sb,
      SUM(batter_game.hits) / SUM(batter_game.at_bats) as avg,
      SUM((batter_game.homeruns * 3) + (batter_game.triples * 2) + batter_game.doubles) / SUM(batter_game.at_bats) as iso,
      SUM(batter_game.total_bases) / SUM(batter_game.at_bats) as slg,
      SUM(batter_game.walks) / SUM(batter_game.plate_appearances) as bb_pct,
      SUM(batter_game.strikeouts) / SUM(batter_game.plate_appearances) as so_pct,
      SUM(batter_game.plate_appearances) as pa,
      SUM(batter_game.on_base) / SUM(batter_game.plate_appearances) as obp
    ";

    $sql_join = "
    FROM batter_game
    INNER JOIN player_game ON player_game.id = batter_game.player_game_id
    INNER JOIN player ON player_game.player_id = player.id
    INNER JOIN game ON player_game.game_id = game.id
    INNER JOIN team ON player_game.team_id = team.id
    INNER JOIN league ON team.league_id = league.id
    INNER JOIN class ON league.class_id = class.id
    INNER JOIN ($sql_age) as ages ON ages.lid = league.id
    ";

    $sql_where = "
    WHERE (DATE(game.game_date) BETWEEN '$start_date' AND '$end_date')
    ";
  
    if($league != "ALL")
      $sql_where .= " AND league.name = " . "'$league' ";
    
    if($team != "ALL")
      $sql_where .= " AND team.parent_team_abbrev = " . "'$team' ";

    $sql_group = "
    GROUP BY player.id
    HAVING SUM(batter_game.plate_appearances) >= $pa
    ";
    //GROUP BY player.id, league.id, team.id

    $sql_order = "
    ORDER BY age_diff ASC
    ";

    $offset = ($page - 1) * 20;
    $sql_limit = "
    LIMIT 20 OFFSET $offset
    ";

    $sql = $sql_select . $sql_join;
    $sql .= $sql_where;
    $sql .= $sql_group . $sql_order . $sql_limit;

    echo '<!--', $sql, '-->';
   
    $result = mysqli_query($db,$sql);

    //$stmt = $db->prepare($sql);
    //$stmt->bind_param('ss', $start_date, $end_date);

    //$stmt->execute();
    //$result = $stmt->get_result();
    if(! $result)
    {
      echo 'abcdefg';
      echo '<!--', mysqli_error($db), '-->';
    }
    return $result;
    //$sql = $db->real_escape_string($sql);

    //return mysqli_query($db,$sql);
  }

  function getLeagues()
  {
    include("../resources/config.php");
    $sql = "SELECT name FROM league ORDER BY name ASC";
    return mysqli_query($db,$sql);
  }
  
  function getTeams()
  {
    include("../resources/config.php");
    $sql = "SELECT DISTINCT parent_team_abbrev AS name FROM team ORDER BY name ASC";
    return mysqli_query($db,$sql);
  }
?>

<html>
  <head>
    <!-- FONT
    –––––––––––––––––––––––––––––––––––––––––––––––––– -->
    <link href="//fonts.googleapis.com/css?family=Raleway:400,300,600" rel="stylesheet" type="text/css">

    <!-- CSS
    –––––––––––––––––––––––––––––––––––––––––––––––––– -->
    <link rel="stylesheet" href="css/normalize.css">
    <link rel="stylesheet" href="css/skeleton.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/jquery.loading-indicator.css">
    <title>MiLB Splits</title>
    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <script>
    $( function() {
      $( "#datepicker_start" ).datepicker();
      $( "#datepicker_end" ).datepicker();
    } );
    </script>
    <script type="text/javascript" src="js/jquery.tablesorter.js"></script> 
    <script type="text/javascript" src="js/jquery.freezeheader.js"></script> 
    <script type="text/javascript" src="js/jquery.loading-indicator.js"></script> 
    <script>
    $(document).ready(function() 
      { 
        $("#myTable").tablesorter(); 
        $("#myTable").freezeHeader();
        //$('body').loadingIndicator();
        enableCustom();
      } 
    );
    </script>
    <script type="text/javascript">
      function showLoading() {
        $('body').loadingIndicator();
      }
      function enableCustom() {
        //alert(jQuery("#myDaysSelect option:selected").val());
        if(jQuery("#myDaysSelect option:selected").val() == 'Custom')
        {
          $("#datepicker_start").prop('disabled', false);
          $("#datepicker_end").prop('disabled', false);
        }
        else
        {
          $("#datepicker_start").prop('disabled', true);
          $("#datepicker_end").prop('disabled', true);
        }
      }
    </script>
  </head>
  <body>
    <h1>MiLB Splits</h1>
    <form id="myForm" action="" method="get" onsubmit="showLoading();">
      <fieldset data-role="controlgroup" data-type="horizontal">
      <label>Range:</label>
      <select name="days" id="myDaysSelect" onchange="enableCustom();">
        <?php
          $ranges = [
            "7" => "Last 7 Days",
            "15" => "Last 15 Days",
            "30" => "Last 30 Days",
            "60" => "Last 60 Days",
            "Custom" => "Custom Range"
          ];
          foreach($ranges as $key => $value)
          {
            //echo "<!--", $key, " = ", $value, " : ", $days, "-->";
            echo "<option value=\"$key\"";
            if($days == $key)
              echo ' selected="selected"';
            echo ">$value</option>";
          }
        ?>
      </select>
      <label>Custom Range:</label>
      <?php
        echo '<input type="text" id="datepicker_start" name="startdate" value="', date('m/d/Y', strtotime($start)), '"/>';
      ?>
      to
      <?php
        echo '<input type="text" id="datepicker_end" name="enddate" value="', date('m/d/Y', strtotime($end)), '"/>';
      ?>
      <br/>
      <label>League:</label>
      <select name="league">
        <option value="ALL">ALL</option>
        <?php
          $result = getLeagues();
          while($row = mysqli_fetch_array($result)) {
            $name =  $row['name'];
            echo '<option';
            if($name == $league)
              echo ' selected="selected"';
            echo '>';
            echo $name;
            echo '</option>';
          }
        ?>
      </select>
      <label>Team:</label>
      <select name="team">
        <option value="ALL">ALL</option>
        <?php
          $result = getTeams();
          while($row = mysqli_fetch_array($result)) {
            $name = $row['name'];
            echo '<option';
            if($name == $team)
              echo ' selected="selected"';
            echo '>';
            echo $name;
            echo '</option>';
          }
        ?>
      </select>
      <label>Min PA:</label>
      <select name="pa">
        <?php
        $pa_array = array(50, 100, 150, 200, 250, 300, 350, 400, 450, 500);
        foreach($pa_array as $pa_temp)
        {
          echo '<option';
          if($pa == $pa_temp)
            echo ' selected="selected"';
          echo '>';
          echo $pa_temp;
          echo '</option>';
        }
        ?>
      </select>
      <input type="hidden" name="page" value="2"/>
      <input type="submit" name="submit"/>
      </fieldset>
    </form>
    <div id="data_table" align="left">
    <?php
      $result = getStats($start, $end, $league, $team, $pa, $page);
    ?>
    <table id="myTable" class="tablesorter">
      <colgroup>
      <col span="7">
      <col span="4" style="background-color: whitesmoke">
      <col span="3">
      <col span="3" style="background-color: whitesmoke">
      </colgroup>
      <thead>
        <tr class="header">
          <th>Name</th>
          <th>Pos</th>
          <th>Class</th>
          <th>Lg</th>
          <th>Team</th>
          <th>Age</th>
          <th>Age&Delta;</th>
          <th>PA</th>
          <th>XBH</th>
          <th>HR</th>
          <th>SB</th>
          <th>BB%</th>
          <th>K%</th>
          <th>ISO</th>
          <th>AVG</th>
          <th>OBP</th>
          <th>SLG</th>
        </tr>
      </thead>
      <tbody>
        <?php
          if(mysqli_num_rows($result) > 0)
          {
            while($row = mysqli_fetch_array($result)) {
              $age = number_format(round($row['age'], 1), 1);
              $age_diff = number_format(round($row['age_diff'], 1), 1);
              if($age_diff >= 0)
                $age_diff = '+' . $age_diff;
              $avg = ltrim(number_format(round($row['avg'], 3), 3), '0');
              $obp = ltrim(number_format(round($row['obp'], 3), 3), '0');
              $iso = ltrim(number_format(round($row['iso'], 3), 3), '0');
              $slg = ltrim(number_format(round($row['slg'], 3), 3), '0');
              $bb_pct = number_format(round($row['bb_pct'] * 100, 1), 1) . '%';
              $so_pct = number_format(round($row['so_pct'] * 100, 1), 1) . '%';
              echo '<tr>';
              echo '<td>', $row['lastname'], ', ', $row['firstname'];
              echo '<a target="_blank" href="http://www.fangraphs.com/players.aspx?lastname=', $row['firstname'], ' ', $row['lastname'], '"><img src="https://www.google.com/s2/favicons?domain=http://www.fangraphs.com" height="12" width="12" border="0" style="margin:0 3px 0 6px"></a>';
              echo '<a target="_blank" href="http://www.baseballprospectus.com/player_search.php?search_name=', $row['firstname'], ' ', $row['lastname'], '"><img src="https://www.google.com/s2/favicons?domain=http://www.baseballprospectus.com" height="12" width="12" border="0" style="margin:0 3px 0 3px"></a>';
              echo '</td>';
              echo '<td>', $row['position'], '</td>';
              echo '<td>', $row['class'], '</td>';
              echo '<td>', $row['league'], '</td>';
              echo '<td>', $row['team'], '</td>';
              echo '<td>', $age, '</td>';
              echo '<td>', $age_diff, '</td>';
              echo '<td>', $row['pa'], '</td>';
              echo '<td>', $row['xbh'], '</td>';
              echo '<td>', $row['hr'], '</td>';
              echo '<td>', $row['sb'], '</td>';
              echo '<td>', $bb_pct, '</td>';
              echo '<td>', $so_pct, '</td>';
              echo '<td>', $iso, '</td>';
              echo '<td>', $avg, '</td>';
              echo '<td>', $obp, '</td>';
              echo '<td>', $slg, '</td>';
              echo '</tr>';
            }
          }
        ?>
      </tbody>
    </table>
    <?php
    $curPage = $_GET['page'];
    if($curPage > 1)
    {
      $_GET['page'] = $curPage - 1;
      echo '<a href="splits.php?'.http_build_query($_GET).'" onclick="showLoading();" >Previous</a>';
    }

    $_GET['page'] = $curPage + 1;
    echo ' <a href="splits.php?'.http_build_query($_GET).'" onclick="showLoading();" >Next</a>';

    if(mysqli_num_rows($result) == 0)
    {
      echo '<b>No Results!</b>';
    }
    ?>
    </div>
  </body>
</html>

