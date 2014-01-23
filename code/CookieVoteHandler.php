<?php

/**
 * Default vote handler.
 *
 * If a user votes for a poll and Cookie is stored on their computer which assigns
 * that they have voted for a particular poll ID
 *
 */
class CookieVoteHandler extends Vote_Backend {

	const COOKIE_PREFIX = 'SSPoll_';

	/**
	 * Generates a cookie to indicate the user has voted on the poll.
	 */
	public function markAsVoted() {
		Cookie::set(self::COOKIE_PREFIX . $this->getPoll()->ID, 1);
	}

	/**
	 * Checks to see if the user can vote. Verified by checking whether or not
	 * the user has voted before. Which is the only limitation of this
	 * cookie based implementation.
	 *
	 * @return bool
	 */
	public function canVote() {
		return !$this->hasVoted();
	}

	/**
	 * Checks to see if the user has a cookie set which is generated when
	 * they vote on a poll
	 *
	 * @return bool
	 */
	public function hasVoted() {
		return (bool) Cookie::get(self::COOKIE_PREFIX . $this->getPoll()->ID);
	}

}
