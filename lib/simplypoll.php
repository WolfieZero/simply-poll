<?php

class SimplyPoll{

	public $pollData;

	public function __construct(){
		global $wp_scripts;
		wp_enqueue_script('jquery');
		if (!in_array('jquery', $wp_scripts->done) && !in_array('jquery', $wp_scripts->in_footer)) {
			$wp_scripts->in_footer[] = 'jquery';
		}
		wp_enqueue_script('jSimplyPoll', plugins_url('/js/simplypoll.js', SP_FILE));
		if (!in_array('jSimplyPoll', $wp_scripts->done) && !in_array('jSimplyPoll', $wp_scripts->in_footer)) {
			$wp_scripts->in_footer[] = 'jSimplyPoll';
		}
	}


	public function displayPoll($args){

		if(isset($args['id'])){
			$id			= $args['id'];
			$poll		= $this->grabPoll($id);
			$question	= $poll['question'];
			$answers	= $poll['answers'];

			$data = include(SP_DIR.'page/user/poll-display.php');

			return $data;
		}
	}

	public function submitPoll($pollID, $vote=null){
		$polls = $this->grabPoll();

		if($vote){
			$current = (int)$polls['polls'][$pollID]['answers'][$vote]['vote'];
			++$current;
			$polls['polls'][$pollID]['answers'][$vote]['vote'] = $current;

			$totalVotes = 0;

			foreach($polls['polls'][$pollID]['answers'] as $key => $aData){
				$totalVotes = $totalVotes + $aData['vote'];
			}

			$polls['polls'][$pollID]['totalvotes'] = $totalVotes;

			$success = $this->setPollDB($polls);
			$polls['voted'] = $vote;
		}

		return json_encode($polls['polls'][$pollID]);

	}


	/**
	 * Grab Poll Info
	 *
	 * @param	$id
	 * @return	array
	 */
	public function grabPoll($id=null){
		$poll = $this->getPollDB($id);
		if (isset($poll[0])) {
			$poll = $poll[0];
			$poll['answers'] = unserialize($poll['answers']);
		}
		return $poll;
	}


	/**
	 * Save poll data to DB
	 *
	 * @param	$pollData
	 * @return	bool
	 */
	public function setPollDB(array $pollData){
		$serialized = serialize($pollData);
		return update_option('simplyPoll', $serialized);
	}

	/**
	 * Save poll data to DB when voting
	 *
	 * @param	$pollData
	 * @return	bool
	 */
	public function votePollDB(array $pollData){
		echo 'Voted on the poll, updating db now';
	}

	/**
	 * Save poll data to DB when updating a poll
	 *
	 * @param	$pollData
	 * @return	bool
	 */
	public function updatePollDB(array $pollData){
		global $wpdb;
		
		$wpdb->query("UPDATE `".SP_TABLE."` SET `question`='".$pollData['question']."', `answers`='".mysql_escape_string(serialize($pollData['answers']))."', `updated`='".$pollData['updated']."' WHERE `id`='".$pollData['id']."'");
	}
		
	/**
	 * Save poll data to DB for a new poll
	 * 
	 * @param	$pollData
	 * @return	bool
	 */
	public function newPollDB(array $pollData){
		global $wpdb;
		
		$wpdb->query("INSERT INTO `".SP_TABLE."` (`question`, `answers`, `added`, `active`, `totalvotes`, `updated`) VALUES ('".$pollData['question']."', '".mysql_escape_string(serialize($pollData['answers']))."', '".$pollData['added']."', '".$pollData['active']."', '".$pollData['totalvotes']."', '".$pollData['updated']."')");
	}

	/**
	 * Grab poll data from DB
	 *
	 * @return	array
	 */
	public function getPollDB($id=null){
	
		global $wpdb;
		
		if (isset($id)) {
			$poll = $wpdb->get_results("SELECT * FROM `".SP_TABLE."` WHERE `id`='".$id."'", ARRAY_A);
			return $poll;
		} else {

			if($this->pollData){
				return $this->pollData;
	
			} else {
				$polls['polls'] = $wpdb->get_results("SELECT * FROM `".SP_TABLE."`", ARRAY_A);
				
				if(is_array($polls)){
					for($i=0;$i<count($polls['polls']);$i++) {
						$polls['polls'][$i]['answers'] = unserialize($polls['polls'][$i]['answers']);
					}
				} else {
					$polls = array();
				}
				$this->pollData = $polls;
				return $polls;
			}
		}
	}

}
