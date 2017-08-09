<?php
class qa_ysb_badges {

  const TABLE_NAME = '^ysb_badges';

  /**
   * 保有しているバッチをすべて取得
   * @return [type] [description]
   */
  public function find_by_userid($userid){
    $sql = 'SELECT * FROM ' . self::TABLE_NAME . 'WHERE userid = #';
		return qa_db_read_all_assoc(qa_db_query_sub($sql, $userid));
  }

  /**
   * 保有しているバッチをアクションで指定して取得
   * @return [type] [description]
   */
  private function find_by_action($action){

  }

  /**
   * バッチのレベルを返却。保持していない場合は0を返す
   * @param  [type] $badgeid [description]
   * @param  [type] $userid  [description]
   * @return [type]          [description]
   */
  public function get_badge_level($badgeid, $userid){
    $sql = 'SELECT level FROM ' . self::TABLE_NAME . ' WHERE badgeid = # AND userid = #';
		$level = qa_db_read_one_value(qa_db_query_sub($sql, $badgeid, $userid), true);

    // バッチ自体がないのはレベルを0として扱う
    if($level === null){
      $level = 0;
    }
    return $level;
  }

  /**
   * アクションの状況から付与できるバッチを与える
   *
   * @param [type] $actionid [description]
   * @param [type] $count    [description]
   * @return バッチ付与なら付与したバッチのID, 付与なしならnullを返す
   */
  public function add_badge($userid, $actionid, $count){

    $badges = array();

    foreach(qa_ysb_const::BADGES as $badge){
      if($badge['actionid'] != $actionid){
        continue;
      }

      $current_level = $this->get_badge_level($badge['badgeid'], $userid);

      // 次のレベルを超えている
      $new_level = $current_level;

      for($i=$new_level; $i < 3; $i++) {
        if($count >= $badge['count'][$i]) {
          $new_level++;
        } else{
          break;
        }
      }

      if($new_level > $current_level) {
        $badges[] = array(
          'badgeid' => $badge['badgeid'],
          'level' => $level + 1
        );

        // レベル0 = バッチ未取得 = テーブルにデータがない場合
        if($current_level == 0){
          // insert
          qa_db_query_sub(
            'INSERT INTO ' . self::TABLE_NAME .
            ' ( badgeid, userid, level, created, updated ) '.
            ' VALUES (#, #, #, NOW(), NOW())',
            $badge['badgeid'], $userid, $new_level
          );
          error_log('badge was added, badgeid:' . $badge['badgeid'] . ', userid:' . $userid);

        } else {
          // update
          qa_db_query_sub(
            'UPDATE ' . self::TABLE_NAME .
            ' SET level=#, updated=NOW()'.
            ' WHERE badgeid=# AND userid=#',
            $new_level, $badge['badgeid'], $userid
          );
          error_log('badge was updated, badgeid:' . $badge['badgeid'] . ', userid:' . $userid . ', level:' . $level);
        }
      }
    }
    return $badges;
  }

}