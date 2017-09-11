<?php
require_once( dirname( dirname( dirname( __FILE__ ) ) ).DIRECTORY_SEPARATOR.'system.inc.php' );

switch ( count( $_POST ) ) {
	case 8:
		$valid_units = array( 'HOUR', 'DAY', 'WEEK', 'MONTH', 'QUARTER', 'YEAR' );
		if ( empty( $_POST['date_unit_ago'] ) || !in_array( $_POST['date_unit_ago'], $valid_units, TRUE ) ) {
			exit;
		}
		if ( empty( $_POST['date_group_by'] ) || !in_array( $_POST['date_group_by'], $valid_units, TRUE ) ) {
			exit;
		}
		if ( Events::isValidCombination( $_POST['function'], $_POST['action'] ) === FALSE ) {
			exit;
		}
		$group_by_clause = '';
		switch ( strtolower( $_POST['date_group_by'] ) ) {
			case 'hour':	$group_by_clause .= 'HOUR( `timestamp` ), ';
			case 'day':		$group_by_clause .= 'DAY( `timestamp` ), ';
			case 'week':	$group_by_clause .= 'WEEK( `timestamp` ), ';
			case 'month':	$group_by_clause .= 'MONTH( `timestamp` ), ';
			case 'quarter':	$group_by_clause .= 'QUARTER( `timestamp` ), ';
			case 'year':	$group_by_clause .= 'YEAR( `timestamp` )';
			break;
			default:
				exit;
			break;
		}
		switch ( strtolower( $_POST['style'] ) ) {
			case 'chart':
				$result = $pdo_handler->query( 'SELECT COUNT( * ) AS `count`, HOUR( `timestamp` ) AS `hour`, DAY( `timestamp` ) AS `day`, WEEK( `timestamp` ) AS `week`, MONTH( `timestamp` ) AS `month`, QUARTER( `timestamp` ) AS `quarter`, YEAR( `timestamp` ) AS `year`
												FROM `etc_events`
												WHERE
													`function` = :function AND
													`action` = :action AND '.
													( ( intval( $_POST['user_id'], 10 ) > 0 ) ? '`author_user_id` = '.$pdo_handler->quote( intval( $_POST['user_id'], 10 ) ).' AND ' : '' ).'
													`affected_object_id` = :object_id AND
													`timestamp` > DATE_SUB( NOW(), INTERVAL '.intval( $_POST['date_how_many_units_ago'], 10 ).' '.$_POST['date_unit_ago'].' )
												GROUP BY '.$group_by_clause.'
												ORDER BY `timestamp` ASC;',
												array( ':function' => $_POST['function'],
														':action' => $_POST['action'],
														':object_id' => $_POST['object_id'] ) );
				if ( !empty( $result ) ) {
					printChart( $result, $_POST['date_group_by'] );
				}
			break;

			case 'table':
				$result = $pdo_handler->query( 'SELECT `timestamp`, `author_user_id`
												FROM `etc_events`
												WHERE
													`function` = :function AND
													`action` = :action AND '.
													( ( intval( $_POST['user_id'], 10 ) > 0 ) ? '`author_user_id` = '.$pdo_handler->quote( intval( $_POST['user_id'], 10 ) ).' AND ' : '' ).'
													`affected_object_id` = :object_id AND
													`timestamp` > DATE_SUB( NOW(), INTERVAL '.intval( $_POST['date_how_many_units_ago'], 10 ).' '.$_POST['date_unit_ago'].' )
												ORDER BY `timestamp` DESC;',
												array( ':function' => $_POST['function'],
														':action' => $_POST['action'],
														':object_id' => $_POST['object_id'] ) );
				if ( !empty( $result ) ) {
					printTable( $result, $_POST['user_id'] );
				}
			break;

			default:
				exit;
			break;
		}
	break;

	default:
		exit;
	break;
}


//*************************************************************************************************
// FUNCTIONS
//*************************************************************************************************

function printRowDate ( $db_row, $date_group_by ) {
	switch ( strtolower( $date_group_by ) ) {
		case 'hour':	echo $db_row['hour'].'<br />'.$db_row['day'].'/'./*$db_row['week'].'<br />'.*/$db_row['month'].'/'./*$db_row['quarter'].'<br />'.*/$db_row['year'];
		break;
		case 'day':		echo						  $db_row['day'].'/'./*$db_row['week'].'<br />'.*/$db_row['month'].'/'./*$db_row['quarter'].'<br />'.*/$db_row['year'];
		break;
		case 'week':	echo											 /*$db_row['week'].'<br />'.*/$db_row['month'].'/'./*$db_row['quarter'].'<br />'.*/$db_row['year'];
		break;
		case 'month':	echo																		  $db_row['month'].'/'./*$db_row['quarter'].'<br />'.*/$db_row['year'];
		break;
		case 'quarter':	echo																							   /*$db_row['quarter'].'<br />'.*/$db_row['year'];
		break;
		case 'year':	echo																															   $db_row['year'];
		break;
		default:		exit;
		break;
	}
}

function printChart ( $db_rows, $date_group_by, $height_multiplier = 0 ) {
	if ( $height_multiplier === 0 ) {
		switch ( strtolower( $date_group_by ) ) {
			case 'hour':	$height_multiplier = 4;
			break;
			case 'day':		$height_multiplier = 3;
			break;
			case 'week':	$height_multiplier = 2;
			break;
			case 'month':	$height_multiplier = 2;
			break;
			case 'quarter':	$height_multiplier = 1;
			break;
			case 'year':	$height_multiplier = 1;
			break;
			default:		exit;
			break;
		}
	}
	foreach ( $db_rows as $db_row ) {
		?>
		<div class="selection" style="display:inline-block; margin:2px; text-align:center; vertical-align:baseline; cursor:initial;">
			<div style="width:20px; margin:0px auto;">
				<?php echo $db_row['count']; ?>
				<div title="<?php echo $db_row['count']; ?>" class="bg_darker" style="height:<?php echo $db_row['count'] * $height_multiplier; ?>px;"></div>
			</div>
			<div class="tx_smaller" style="min-width:26px; /*max-width:80px;*/ margin:0px auto; padding:0px 4px; border-bottom:2px solid black;">
				<?php printRowDate( $db_row, $date_group_by ); ?>
			</div>
		</div>
		<?php
	}
}

function printTable ( $db_rows, $author_user_id ) {
	global $pdo_handler;
	$user_names = array();
	?>
	<table class="list" style="width:initial; margin:0px auto;">
		<colgroup>
			<col span="1" style="/*width:30%;*/">
			<col span="1" style="/*width:70%;*/">
		</colgroup>
		<tr>
			<th>Data</th>
			<th>Autor</th>
		</tr>
		<?php
		foreach ( $db_rows as $db_row ) {
			?>
			<tr>
				<td style="text-align:right; padding-right:16px;"><?php echo formatTimestamp( $db_row['timestamp'] ); ?></td>
				<td style="text-align:left;"><?php echo $pdo_handler->query_object_name_from_id( 'user', $db_row['author_user_id'], $user_names ); ?></td>
			</tr>
			<?php
		}
	?>
	</table>
	<?php
}
?>
