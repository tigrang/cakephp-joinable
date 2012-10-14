<?php
App::uses('ModelBehavior', 'Model');
/**
 * JoinableBehavior
 *
 * Options:
 * 	`type` - Type of join to use. Default: 'LEFT'
 *	`conditions` - Conditions to join on, if `true` is used, conditions will be set automatically. Default: true
 *
 * Usage:
 * 	$this->User->find('all', array(
 * 		'joins' => array(
 * 			'Profile' => array(
 * 				'type' => 'RIGHT',
 * 			),
 * 			'Subscription' => array(
 * 				'conditions' => '`Subscription`.`id` = `User`.`subscription_id` AND `Subscription`.`active` = 1',
 * 				'SubscriptionPlan',
 * 			),
 * 		),
 * 	));
 */
class JoinableBehavior extends ModelBehavior {

	/**
	 * List of parsed joins for current find call
	 *
	 * @var array
	 */
	protected $_parsed;

	/**
	 * Parses `joins` key and replaces it with parsed one which the datasource can work with
	 *
	 * @param Model $Model
	 * @param array $query
	 * @return array|bool
	 */
	public function beforeFind(Model $Model, $query) {
		if (isset($query['joins'])) {
			$this->_parsed = array();
			$query['joins'] = $this->_parseJoins($Model, $query['joins']);
		}
		return $query;
	}

	/**
	 * Recursively parses joins
	 *
	 * @param Model $Model
	 * @param array $joins
	 * @return array
	 */
	protected function _parseJoins(Model $Model, $joins) {
		$defaults = array('type' => 'LEFT', 'conditions' => true);
		foreach($joins as $association => $options) {
			if (is_string($options)) {
				$association = $options;
				$options = array();
			}
			$deeperAssociations = array_diff_key($options, $defaults);
			$options = array_merge($defaults, $options);
			$this->_join($Model, $association, $options['conditions'], $options['type']);
			if (!empty($deeperAssociations)) {
				$this->_parseJoins($this->_associatedModel($Model, $association), $deeperAssociations);
			}
		}
		return $this->_parsed;
	}

	/**
	 * Parses relationship between associations and appends created join array to parsed list
	 *
	 * @param Model $Model
	 * @param string $modelAlias
	 * @param string $association
	 * @param string $associationAlias
	 * @param string|array|boolean $conditions
	 * @param string$type
	 */
	protected function _join(Model $Model, $association, $conditions, $type) {
		$AssociatedModel = $this->_associatedModel($Model, $association);
		if ($conditions === true) {
			$primaryModel = $primaryKey = $joinModel = $foreignKey = null;
			if (array_key_exists($association, $Model->belongsTo)) {
				$primaryModel = $association;
				$primaryKey = $AssociatedModel->primaryKey;
				$joinModel = $Model->alias;
				$foreignKey = $Model->belongsTo[$association]['foreignKey'];
			} elseif (array_key_exists($association, $Model->hasOne)) {
				$primaryModel = $Model->alias;
				$primaryKey = $Model->primaryKey;
				$joinModel = $association;
				$foreignKey = $Model->hasOne[$association]['foreignKey'];
			} elseif (array_key_exists($association, $Model->hasMany)) {
				$primaryModel = $Model->alias;
				$primaryKey = $Model->primaryKey;
				$joinModel = $association;
				$foreignKey = $Model->hasMany[$association]['foreignKey'];
			} elseif (array_key_exists($association, $Model->hasAndBelongsToMany)) {
				$relation = $Model->hasAndBelongsToMany[$association];
				$conditions = $this->_createCondition(
					$Model->alias, $Model->primaryKey, $relation['with'], $relation['foreignKey']
				);
				$this->_addJoin($relation['joinTable'], $relation['with'], 'LEFT', $conditions);
				$primaryModel = $association;
				$primaryKey = $AssociatedModel->primaryKey;
				$joinModel = $relation['with'];
				$foreignKey = $relation['associationForeignKey'];
			}
			$conditions = $this->_createCondition($primaryModel, $primaryKey, $joinModel, $foreignKey);
		}
		$this->_addJoin($AssociatedModel->table, $association, $type, $conditions);
	}

	/**
	 * Creates a condition string
	 *
	 * @param $primaryModel
	 * @param $primaryKey
	 * @param $joinModel
	 * @param $foreignKey
	 * @return string
	 */
	protected function _createCondition($primaryModel, $primaryKey, $joinModel, $foreignKey) {
		return sprintf('%s.%s = %s.%s',
			$primaryModel, $primaryKey, $joinModel, $foreignKey
		);
	}

	/**
	 * Creates join item that the datasource can parse
	 *
	 * @param $table
	 * @param $alias
	 * @param $type
	 * @param $conditions
	 */
	protected function _addJoin($table, $alias, $type, $conditions) {
		$foreignKey = false;
		$this->_parsed[] = compact('table', 'alias', 'type', 'foreignKey', 'conditions');
	}

	/**
	 * Returns associated model
	 *
	 * @param Model $Model
	 * @param $association
	 * @return mixed
	 * @throws MissingModelException
	 */
	protected function _associatedModel(Model $Model, $association) {
		if (!isset($Model->{$association})) {
			throw new MissingModelException(array($association));
		}
		return $Model->{$association};
	}

}