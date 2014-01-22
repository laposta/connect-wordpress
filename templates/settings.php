<?php
class Laposta_Settings {

	private $options;
	private $status = '';
	private $lists;

	public function __construct($options) {

		$this->options = $options;

		// api-key present?
		if (!$this->options['api_key']) {
			$this->status = 'no_api_key';
			return;
		}

		// curl should be present
		if (!function_exists('curl_init')) {
			$this->status = 'no_url';
			return;
		}

		// include laposta api wrapper
		include(sprintf("%s/../includes/laposta-php-1.2/lib/Laposta.php", dirname(__FILE__))); 
		Laposta::setApiKey($this->options['api_key']);

		// try to fetch lists
		$list = new Laposta_List();
		try {
			$result = $list->all();
			//print '<pre>';print_r($result);print '</pre>';

			// lists present?
			if (!$result['data']) {
				$this->status = 'no_lists';
			} else {
				$this->lists = $result['data'];
				$this->status = 'ok';
			}

		} catch (Exception $e) {

			// information about the error
			$error = $e->json_body['error'];
			//print_r($error);

			if ($error) {

				// invalid request?
				if ($error['type'] == 'invalid_request') {

					// this means api-key is incorrect
					$this->status = 'invalid_api_key';
				}
			}

			if (!$this->status) {

				// different exception
				$this->status = 'error-api: ' . print_r($e, 1);
			}
		}
	}

	public function getHtmlTitle() {
	// html for setting the title

		$html = '
<tr valign="top">
	<th scope="row"><label for="title">Kop boven formulier</label></th>
	<td><input type="text" name="title" id="title" value="' . $this->options['title'] . '" /></td>
</tr>';

		return $html;
	}


	public function getHtmlApiKey() {
	// html for setting the api-key

		$html = '
<tr valign="top">
	<th scope="row"><label for="api_key">API key</label></th>
	<td><input type="text" name="api_key" id="api_key" value="' . $this->options['api_key'] . '" /></td>
</tr>';

		return $html;
	}

	public function getHtmlLists() {
	// html for choosing al list

		// start html
		$html = '<tr valign="top">';
		$html .= '<th scope="row"><label for="setting_b">In welke lijst moeten de inschrijvingen terecht komen?</label></th>';
		$html .= '<td>';

		// status ok?
		if ($this->status != 'ok') {

			// not ok, show message
			$html .= $this->getMessage();
		} else {

			// ok, show lists
			$html .= $this->getHtmlListOptions();
		}

		// end html
		$html .= '</td>';
		$html .= '</tr>';

		return $html;
	}

	private function getHtmlListOptions() {

		$html = '';
		foreach($this->lists as $item) {

			$list = $item['list'];
			//print_r($list);

			// value is combination of account and list id, to be used in javascript later
			$value = $list['account_id'] . '/l/' . $list['list_id'];
			$id = 'id-' . $list['list_id'];

			$html .= '<input type="radio" name="list" value="' . $value . '" id="' . $id . '"';

			// check if selected
			if ($value == $this->options['list']) {
				$html .= ' checked';
			}
			$html .= ' />';
			$html .= '<label for="' . $id . '">';
			$html .= htmlspecialchars($list['name']);
			$html .= '</label>';
			$html .= '<br />';
		}

		return $html;
	}

	private function getMessage() {
	// return message based on status

		if (strpos($this->status, 'error-api') !== false) {

			// something went wrong with the api
			return 'Contact met de api lukt niet. Mail deze foutmelding naar stijn@laposta.nl voor een oplossing.<br><pre>' . $this->status . '</pre>';
		}
		if ($this->status == 'no_api_key') return 'Nog geen api-key ingevuld.';
		if ($this->status == 'no_curl') return 'Deze plugin heeft de php-curl extensie nodig, maar deze is niet geinstalleerd.';
		if ($this->status == 'invalid_api_key') return 'Dit is geen geldige api-key.';
		if ($this->status == 'no_lists') return 'Geen lijsten gevonden.';

		return 'Er is een onbekend probleem opgetreden.';
	}
}

// initialize settings object
$settings = new Laposta_Settings(array(
	'title' => get_option('title'),
	'api_key' => get_option('api_key'),
	'list' => get_option('list'),
));
?>
<div class="wrap">
    <h2>Laposta instellingen</h2>
    <form method="post" action="options.php"> 
        <?php @settings_fields('laposta_template-group'); ?>
        <?php @do_settings_fields('laposta_template-group'); ?>

        <table class="form-table">  
		<?php echo $settings->getHtmlTitle(); ?>
		<?php echo $settings->getHtmlApiKey(); ?>
		<?php echo $settings->getHtmlLists(); ?>
        </table>

        <?php @submit_button(); ?>
    </form>
</div>
