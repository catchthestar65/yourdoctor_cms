<?php
/**
 * Uninstall handler for YD Supervisor.
 *
 * 設計書 11. 未確定論点 #5 の決定（データ保持）に従い、何も削除しない。
 * yd_doctor 投稿・ACFメタは plugin 削除後もDBに残る。
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// no-op
