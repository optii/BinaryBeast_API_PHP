<?php

require_once('lib/includes.php');

/**
 * Test local mysql caching 
 * @group cache
 * @group library
 * @group all
 */
class BBCacheTest extends bb_test_case {
    
    /**
     * @var BinaryBeast
     */
    protected $object;

    protected function setUp() {
        $this->object = &$this->bb;
    }

    public function test_list_tournaments() {
        $result = $this->bb->call('Tourney.TourneyList.Creator', null, 2, BBCache::TYPE_TOURNAMENT);
        $this->assertServiceListSuccessful($result, 'list');
        //
        $result = $this->bb->call('Tourney.TourneyList.Creator', null, 2, BBCache::TYPE_TOURNAMENT);
        $this->assertServiceListSuccessful($result, 'list');
        $this->assertServiceLoadedFromCache($result);
    }
    public function test_clear_svc() {
        $result = $this->bb->call('Tourney.TourneyList.Creator', null, 2, BBCache::TYPE_TOURNAMENT);
        $this->assertServiceListSuccessful($result, 'list');
        //
        $this->assertTrue($this->bb->clear_cache('Tourney.TourneyList.Creator'));
        //
        $result = $this->bb->call('Tourney.TourneyList.Creator', null, 2, BBCache::TYPE_TOURNAMENT);
        $this->assertServiceNotLoadedFromCache($result);
    }
    public function test_clear_type() {
        //Cache a tour list - with tournament cache type
        $list_result = $this->bb->call('Tourney.TourneyList.Creator', null, 2, BBCache::TYPE_TOURNAMENT);
        $this->assertServiceListSuccessful($list_result, 'list');
        //Cache a team - with tournament cache type
        $team_result = $this->bb->call('Tourney.TourneyLoad.Team', array('tourney_team_id' => 789751), 2, BBCache::TYPE_TOURNAMENT);
        $this->assertServiceSuccessful($team_result);
        //Cache a team - with team cache type
        $team_result2 = $this->bb->call('Tourney.TourneyLoad.Team', array('tourney_team_id' => 789746), 2, BBCache::TYPE_TEAM);
        $this->assertServiceSuccessful($team_result2);
        //Cache game list - with game cache type
        $game_result = $this->bb->call('Game.GameSearch.Search', array('game' => 'quake'), 2, BBCache::TYPE_GAME);
        $this->assertServiceListSuccessful($game_result, 'games');

        //clear all tournament cache
        $this->assertTrue($this->bb->clear_cache(null, BBCache::TYPE_TOURNAMENT));

        //Reload tour list - shouldn't be cached
        $list_result = $this->bb->call('Tourney.TourneyList.Creator', null, 2, BBCache::TYPE_TOURNAMENT);
        $this->assertServiceListSuccessful($list_result, 'list');
        $this->assertServiceNotLoadedFromCache($list_result);
        //Reload 1st team - shouldn't be cached
        $team_result = $this->bb->call('Tourney.TourneyLoad.Team', array('tourney_team_id' => 789751), 2, BBCache::TYPE_TOURNAMENT);
        $this->assertServiceSuccessful($team_result);
        $this->assertServiceNotLoadedFromCache($team_result);
        //Reload 2nd team - should be cached
        $team_result2 = $this->bb->call('Tourney.TourneyLoad.Team', array('tourney_team_id' => 789746), 2, BBCache::TYPE_TEAM);
        $this->assertServiceSuccessful($team_result2);
        $this->assertServiceLoadedFromCache($team_result2);
        //Reload games - should be cached
        $game_result = $this->bb->call('Game.GameSearch.Search', array('game' => 'quake'), 2, BBCache::TYPE_GAME);
        $this->assertServiceListSuccessful($game_result, 'games');
        $this->assertServiceLoadedFromCache($game_result);
    }
    public function test_clear_id() {
        //Cache a tournament, specify cache id
        $result = $this->object->call('Tourney.TourneyLoad.Info', array('tourney_id' => 'xQL1302101'), 2, BBCache::TYPE_TOURNAMENT, 'xQL1302101');
        $this->assertServiceSuccessful($result);
        //cache a second tournament
        $result = $this->object->call('Tourney.TourneyLoad.Info', array('tourney_id' => 'xSC213021613'), 2, BBCache::TYPE_TOURNAMENT, 'xSC213021613');
        $this->assertServiceSuccessful($result);

        //Clear cache for second tour
        $this->assertTrue($this->object->clear_cache(null, null, 'xSC213021613'));

        //Reload first - should be 
        $result = $this->object->call('Tourney.TourneyLoad.Info', array('tourney_id' => 'xQL1302101'), 2, BBCache::TYPE_TOURNAMENT, 'xQL1302101');
        $this->assertServiceLoadedFromCache($result);
        //Reload second - shoudl not be cached
        $result = $this->object->call('Tourney.TourneyLoad.Info', array('tourney_id' => 'xSC213021613'), 2, BBCache::TYPE_TOURNAMENT, 'xSC213021613');
        $this->assertServiceNotLoadedFromCache($result);
    }
    /**
     * @group new
     */
    public function test_clear_expired() {
        //Delete first in case already cached from previous test
        $this->assertTrue($this->bb->clear_cache(null, null, 'xQL1302101'));

        //Cache a tournament, specify negative ttl
        $result = $this->object->call('Tourney.TourneyLoad.Info', array('tourney_id' => 'xQL1302101'), -1, null, 'xQL1302101');
        $this->assertServiceSuccessful($result);

        //clear expired cache
        $this->assertTrue($this->object->clear_expired_cache());

        //Reload - shouldn't be cached
        $result = $this->object->call('Tourney.TourneyLoad.Info', array('tourney_id' => 'xQL1302101'), -1);
        var_dump($result);
        $this->assertServiceNotLoadedFromCache($result);
    }

}
