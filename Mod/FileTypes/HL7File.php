<?php

class HL7File implements FileType {

    public static function isOfThisDataType($file, $extension) {
        return TRUE;
    }

    private static function table($digit) {
        $table = array();
        $table = preg_split("/[\s]+/", $digit, NULL, PREG_SPLIT_NO_EMPTY);
        return $table;
    }

    public static function getImportableData($file) {

        echo <<<END
		<table class="bordered-table">
			<tr>
				<th><input type="checkbox" value="option1" name="optionsCheckboxes"/></th>
				<th>SequenceSet</th>
				<th>Sequences</th>
			</tr>
END;

        $dom = new DOMDocument("1.0", "utf-8");
        $dom->load($file);
        $sequence = $dom->getElementsByTagName('sequenceSet')->item(0);
        $startTime = $sequence->getElementsByTagName('head')->item(0)->getAttribute('value');
        $increment = $sequence->getElementsByTagName('increment')->item(0)->getAttribute('value');
        $digits = $sequence->getElementsByTagName('digits');
        /** tableaux[0] = timestamp, tableaux[1+] = valeurs */
        $tableaux = array();
        $i = 1;

        echo "<tr>";
        echo '<td><input type="checkbox" value="option1" name="optionsCheckboxes"/></td>';
        echo "<td>SequenceSet</td>";
        echo <<<END
					<td>
						<table class="zebra-striped bordered-table">
END;

        /** on extrait les séquences */
        foreach ($digits as $digit) {
            $code = $digit->parentNode->parentNode->getElementsByTagName('code')->item(0)->getAttribute('code');

            echo <<<END
							<tr>
								<td><input type="checkbox" value="$code" name="$code" id="$code"/></td>
								<td><label class="td_label" for="$code">Sequence : $code</label></td>
							<tr>
END;

            //$tableaux[$i] = self::table($digit->nodeValue);
            //$i++;
        }
        /* on remplit le timestamp */
        /*for ($j = 0; $j < count($tableaux[1]); $j++) {
         $tableaux[0][] = $startTime + $j * $increment;
         }*/

        //echo "<pre>".print_r($tableaux[0])."</pre>";

        echo <<<END
						</table>
					</td>
				</tr>
END;

        echo <<<END
		</table>
END;

        //partie selection des types de donnée :
        $nameData = "ECG";
        $sum = sha1($nameData);
        echo <<<END
		<p>Vous pouvez choisir de n'importer que certaines données :</p>
		<table class="zebra-striped bordered-table">
			<tr>
				<th><input type="checkbox" value="option1" name="optionsCheckboxes"/></th>
				<th>Nom de la donnée</th>
				<th>Associer la donnée à un relevé</th>
			</tr>
			<tr>
				<td><input type="checkbox" value="ECG" name="data_$sum" id="data_$sum"/></td>
				<td><label class="td_label" for="data_$sum">ECG</label></td>
				<td>
END;

        self::displayDataAssociationChoice($nameData);
        echo <<<END
				</td>
			</tr>
END;

        echo "</table>";

    }

    private static function displayDataAssociationChoice($nameData) {
        $statements_list = DataMod::getStatements($_SESSION['bd_id']);
        $sum = sha1($nameData);
        $new_url = CNavigation::generateUrlToApp('Data', 'choose', array('iframe_mode' => true));
        echo <<<END
		<label for="assoc_$sum">Selectionnez le relevé</label>
		<div class="input">
			<select name="assoc_$sum" id="assoc_$sum">
END;
        foreach ($statements_list as $r) {
            echo '<option value="',              htmlspecialchars($r['name']), '">',              htmlspecialchars($r['name']), " (",              htmlspecialchars($r['modname']), ")", "</option>";
        }
        echo <<<END
			</select>

			<a class="btn" href="$new_url">Nouveau relevé</a>
	    </div>
END;
    }

    public static function submitSelection($data) {

        $dom = new DOMDocument();

        $dom->loadXML($data);

        $sequence = $dom->getElementsByTagName('sequenceSet')->item(0);
        //$startTime = $sequence->getElementsByTagName('head')->item(0)->getAttribute('value');
        $startTime = 0;
        $increment = $sequence->getElementsByTagName('increment')->item(0)->getAttribute('value');
        $digits = $sequence->getElementsByTagName('digits');
        /** tableaux[0] = timestamp, tableaux[1+] = valeurs */
        $tableaux = array();
        $i = 1;

        /** on extrait les séquences */
        foreach ($digits as $digit) {
            $code = $digit->parentNode->parentNode->getElementsByTagName('code')->item(0)->getAttribute('code');

            if (isset($_POST[$code])) {
                $tableaux['names'][$i] = $code;
                $tableaux[$i] = self::table($digit->nodeValue);
                $i++;
            }
        }
        /* on remplit le timestamp */
        for ($j = 0; $j < count($tableaux[1]); $j++) {
            $tableaux['timestamp'][] = $startTime + $j * $increment;
        }

        R::begin();

        //remplissage relevé par relevé
        foreach ($_POST as $key => $post) {
            if (self::startswith($key, "assoc_")) {
                $sum_assoc = strrchr($key, '_');
                if (isset($_POST['data' . $sum_assoc])) {
                    self::saveData($post, $_POST['data' . $sum_assoc], $tableaux);
                }
            }
        }

        R::commit();
        new CMessage('Vos relevés ont été ajoutés avec succès ! Vous pouvez en sélectionner d\'autres, ou bien revenir au Tableau de Bord.');
        CNavigation::redirectToApp('Import', 'dataSelection');
    }

    private static function saveData($name_statement_prefix, $type_Datas, $tableaux) {
        
        $multi_releve = new StatementComposition($name_statement_prefix,$_SESSION['user']);
        

        for ($sequence = 1; $sequence < count($tableaux) - 1; $sequence++) {

            $name_statement = $name_statement_prefix . " (" . $tableaux['names'][$sequence] . ")";

            $r = self::create_releve($name_statement);

            $statement = DataMod::getStatement($name_statement, $_SESSION['bd_id']);

            //echo print_r($statement) . "\n";

            $b_statement = R::load('releve', $statement['id']);

            if (!$statement)
                CTools::hackError();

            $n_datamod = DataMod::loadDataType($statement['modname']);
            $variables = $n_datamod->getVariables();

            $datamod = $n_datamod->initialize();

            for ($i = 0; $i < count($tableaux['timestamp']); $i++) {

                $datamod->timestamp = $tableaux['timestamp'][$i];

                $datamod->voltage = $tableaux[$sequence][$i];

                //echo print_r($datamod);

                $n_datamod->save($_SESSION['user'], $b_statement, $datamod);
            }
            
            $multi_releve->addStatement($name_statement);

        }
        
        $rTodelete = R::findOne('releve', 'name = ? and user_id = ?', array($name_statement_prefix, $_SESSION['bd_id']));
        R::trash($rTodelete);

    }

    private static function startswith($chaine, $debut) {
        return substr($chaine, 0, strlen($debut)) === $debut;
    }

    private static function create_releve($name) {
        if (!R::findOne('releve', 'name = ? and user_id = ?', array($name, $_SESSION['bd_id']))) {

            $mode = R::findOne('datamod', 'modname = ?', array('ElectroCardioGramme'));

            $user = $_SESSION['user'];

            $statement = R::dispense('releve');
            $statement->mod = $mode;
            $statement->user = $user;
            $statement->name = $name;
            $statement->description = "";

            R::store($statement);

            return $statement;
        }
    }

}
?>
