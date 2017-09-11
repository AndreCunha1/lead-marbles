<?php

class XHTML_Input {

	/* Properties */

	protected $name;
	protected $values_labels;
	protected $ids;
	protected $disableds;
	protected $opening_wrapper = '';
	protected $closing_close = '';


	/* Protected Methods */

	protected function __construct( $values_labels, $name, $ids, $disableds ) {
		$this->values_labels	= $values_labels;
		$this->name				= $name;
		$this->ids				= $ids;
		$this->disableds		= $disableds;
	}

	public function setWrapper( $opening, $close ) {
		$this->opening_wrapper = $opening;
		$this->closing_wrapper = $close;
	}
}

class XHTML_CheckBox extends XHTML_Input {

	/* Properties */

	protected $checkeds;


	/* Public Methods */

	public function __construct( $values_labels, $name, $ids = array(), $checkeds = array(), $disableds = array() ) {
		parent::__construct( $values_labels, $name, $ids, $disableds );

		$this->checkeds = $checkeds;
	}

	public function setProperties( $values_labels, $name, $ids = array(), $checkeds = array(), $disableds = array() ) {
		$this->__construct( $values_labels, $name, $ids, $checkeds, $disableds );
	}

	public function printCheckBox($letra = "") { // TODO: to use the wrapper
		foreach ( $this->values_labels as $value => $label ) {
			if ( array_key_exists( $value, $this->ids ) ) {
				$id  = 'id="'.$this->ids[$value].'"';
				$for = 'for="'.$this->ids[$value].'"';
			} else {
				$id = $for = '';
			}
			$checked	= ( array_key_exists( $value, $this->checkeds ) )	? 'checked="checked"'	: '';
			$disabled	= ( array_key_exists( $value, $this->disableds ) )	? 'disabled="disabled"'	: '';
			?>
			<input type="checkbox" name="<?php echo $this->name; ?>" value="<?php echo $value; ?>" title="<?php echo $letra; ?>" <?php echo $id; ?> <?php echo $checked; ?> <?php echo $disabled; ?> />
			<label <?php echo $for; ?>>
				<?php echo $label; ?>
			</label>
			<br />
			<?php
		}
	}
}

?>
