<?php

/**
 *	Creates an XML in a given format representing a test-question.
 *	One should provide question metadata, such as title,
 *	a question-text, a list of possible answers, a list of correct answers,
 *	some id, and type information (single/multiple - choice).
 */

interface QuestionXMLCreator {

	/**
	 *	Get the xml representation of question.
	 *
	 *	@return	string	$xml
	 */
	public function XML();

	/**
	 *	Set the title of question.
	 *
	 *	@return	questionXMLCreator	$this
	 */
	public function setTitle($title);

	/**
	 *	Set the id of question.
	 *
	 *	@param	string	$id
	 *	@return	questionXMLCreator	$this
	 */
	public function setId($id);	

	/**
	 *	Set the question-text of question.
	 *
	 *	@param	string	$question
	 *	@return	questionXMLCreator	$this
	 */
	public function setQuestion($question);

	/**
	 *	Add an answer option to question.
	 *
	 *	@param	string	$answer
	 *	@param	bool	$correct
	 *	@return	questionXMLCreator	$this
	 */
	public function addAnswer($answer, $correct);

	/**
	 *	Set the type of question.
	 *
	 *	@param	string	$question_type
	 *	@return	questionXMLCreator	$this
	 */
	public function setType($question_type);

	/**
	 * Set the generic feedback,
	 * that will be shown in case of a right and wrong answer.
	 *
	 *	@param	string	$generic_feedback
	 *	@return	questionXMLCreator	$this
	 */
	public function setGenericFeedback($generic_feedback);
}