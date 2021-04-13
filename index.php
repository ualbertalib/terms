<?php
ini_set('display_errors',1);

header('Content-Type:text/html; charset=UTF-8');

// variables for permitted/prohibited qualifiers (this should match up with the available qualifiers in the license module)
//permitted terms will display green checkbox
$permittedQualifier = 'Permitted';

//prohibited terms will display red x
$prohibitedQualifier = 'Prohibited';

/*
**************************************************************************************************************************
** CORAL Licensing Module Terms Tool Add-On Terms Tool Add-On v. 1.0
**
** Copyright (c) 2010 University of Notre Dame
**
** This file is part of CORAL.
**
** CORAL is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
**
** CORAL is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License along with CORAL.  If not, see <http://www.gnu.org/licenses/>.
**
**************************************************************************************************************************
*/

include_once 'directory.php';

$displayHTML = '';
$interLibraryLoadHTML = '';

//get the passed in ISSN or ISBN
if (isset($_GET['issn'])) $issn = $_GET['issn']; else $issn='';
if (isset($_GET['isbn'])) $isbn = $_GET['isbn']; else $isbn='';


//either isbn or isn must be passed in
if (($isbn == '') && ($issn == '')){
	$displayHTML = 'You must pass in either ISSN or ISBN.';
}else{

	try{
		//get targets from the terms tool service for this ISBN or ISSN
		$termsServiceObj = new TermsService(new NamedArguments(array('issn' => $issn, 'isbn' => $isbn)));
		$termsToolObj = $termsServiceObj->getTermsToolObj();


		$targetsArray = array();
		$targetsArray = $termsToolObj->getTargets();


		if (count($targetsArray) == 0){
			$displayHTML = "Sorry, no Full Text Providers are available for this ISSN/ISBN";
		}else{

			//if expression type ID is passed in, display the terms
			if (isset($_GET['typeID'])){
				$typeID = $_GET['typeID'];
				$expressionType = new ExpressionType(new NamedArguments(array('primaryKey' => $typeID)));

				$pageTitle = $expressionType->shortName . " License Terms";
				$displayHTML = "<h2>" . $expressionType->shortName . " terms for <em>" . $termsToolObj->getTitle() . "</em></h2>";

				$displayHTML .= "<ul class='db'>";

				$orderedTargetsArray = array();
				$orderedTargetsArray = $expressionType->reorderTargets($targetsArray);

				$targetArray = array();
				foreach ($orderedTargetsArray as $i => $targetArray){
					$displayHTML .= "<li><h3>" . ($targetArray['public_name']) . "</h3>";

					$expressionArray = array();
					$expressionArray = $expressionType->getExpressionsByResource($targetArray['public_name']);

					//if no expressions are defined for this resource / expression type combination
					if (count($expressionArray) == '0'){
						$displayHTML .= "No " . ($expressionType->shortName) . " terms are defined.";
					}else{

						//loop through each expression for this resource / expression type combination
						foreach ($expressionArray as $expression){
							//get qualifiers into an array
							$qualifierArray = array();
							foreach ($expression->getQualifiers as $qualifier){
								$qualifierArray[] = $qualifier->shortName;
								if (strtoupper($qualifier->shortName) == strtoupper($permittedQualifier)){
									$qualifierImage = "<img src='images/icon_check.gif'>";
								}else if (strtoupper($qualifier->shortName) == strtoupper($prohibitedQualifier)){
									$qualifierImage = "<img src='images/icon_x.gif'>";
								}else{
									$qualifierImage = "";
								}
							}


							//determine document effective date
							$document = new Document(new NamedArguments(array('primaryKey' => $expression->documentID)));

							if ((!$document->effectiveDate) || ($document->effectiveDate == '0000-00-00')){
								$effectiveDate = format_date($document->getLastSignatureDate());
							}else{
								$effectiveDate = format_date($document->effectiveDate);;
							}

							/*$displayHTML .= "Terms as of " . $expression->getLastUpdateDate . ".  ";*/

							$displayHTML .= "The following terms apply ONLY to articles accessed via <a href='" . $targetArray['target_url'] . "' target='_blank'>" . html_entity_decode($targetArray['public_name']) . "</a>";

							$displayHTML .= "<div>";

							$displayHTML .= "<div>";
							$displayHTML .= "<b>" . $expressionType->shortName . " Notes:</b>" . $qualifierImage;

							//start bulletted list
							$displayHTML .= "<ul>\n";

							//first in the bulleted list will be the list of qualifiers, if applicable
							if (count($qualifierArray) > 0){
								$displayHTML .= "<li>" . implode(",", $qualifierArray) . "</li>\n";
							}

							foreach ($expression->getExpressionNotes as $expressionNote){
								$displayHTML .= "<li>" . ($expressionNote->note) . "</li>\n";
							}

							$displayHTML .= "</ul>\n";
							$displayHTML .= "</div>";


							//only display 'show license snippet' if there's actual license document text
							if ($expression->documentText){
								$displayHTML .= "";
                                                                 
                                                                //Custom code by Jeremy March 26 2011,
                                                                // do not display the View License Snippet unless the expression ID=2 (InterlibraryLoans
                                                                if ($expression->expressionID==2){
                                                                    $displayHTML .= "<div id='div_hide_" . $expression->expressionID . "_" . $i . "' >";                                                                   
                                                                    $displayHTML .= "<a href='javascript:void(0);' class='showText smallLink' value='" . $expression->expressionID . "_" . $i . "'><img src='images/arrowright.gif'></a>&nbsp;&nbsp;<a href='javascript:void(0);' class='showText' value='" . $expression->expressionID . "_" . $i . "'>view license snippet</a>";                                                                
                                                                    $displayHTML .= "</div>";
                                                                }else{
                                                                    //$displayHTML .= "<div id='div_hide_" . $expression->expressionID . "_" . $i . "' style='width:600px;'>";                                                                   
                                                                    //$displayHTML .= "<a href='javascript:void(0);' class='showText smallLink' value='" . $expression->expressionID . "_" . $i . "'><img src='images/arrowright.gif'></a>&nbsp;&nbsp;<a href='javascript:void(0);' class='showText' value='" . $expression->expressionID . "_" . $i . "'>view license snippet</a>";                                                                
                                                                    //$displayHTML .= "</div>";                                                                    
                                                                }
								

								$displayHTML .= "<div id='div_display_" . $expression->expressionID . "_" . $i . "' style='display:none; width:600px;'>";
								$displayHTML .= "<a href='javascript:void(0);' class='hideText smallLink' value='" . $expression->expressionID . "_" . $i . "'><img src='images/arrowdown.gif'></a>&nbsp;&nbsp;<a href='javascript:void(0);' class='hideText' value='" . $expression->expressionID . "_" . $i . "'>hide license snippet</a><br />";
								$displayHTML .= "<div class='shaded'>From the license agreement ($effectiveDate):<br><br><i>" . ($expression->documentText) . "</i></div>";
								$displayHTML .= "</div>";

							}

							$displayHTML .= "</li>";

						//end expression loop
						}

					//end expression count
					}

				//target foreach loop
				}
				$displayHTML .= "</ul>";

			//expression type ID was not passed in - find out what expression types are available for these targets and prompt
			}else{
				$pageTitle = "Select Expression Type";

				$expressionTypeObj = new ExpressionType();
				$targetArray = array();
				$uniqueExpressionTypeArray = array();

				foreach ($targetsArray as $i => $targetArray){
					$expressionTypeArray = $expressionTypeObj->getExpressionTypesByResource($targetArray['public_name']);

					//loop through each displayable expression type and add to final array
					foreach ($expressionTypeArray as $expressionTypeID){
						$uniqueExpressionTypeArray[] = $expressionTypeID;
					}

				//end target loop
				}

				//make sure expression type IDs are unique
				$uniqueExpressionTypeArray = array_unique($uniqueExpressionTypeArray);

				if (count($uniqueExpressionTypeArray) == 0){
					$displayHTML = "Sorry, no available license expressions have been located in CORAL Licensing.";
				}else{
					$displayHTML .= "<h2>Conditions of Use</span> for <em>" . ($termsToolObj->getTitle()) . "</em></h2><p>Use of electronic resources licensed for use at the University of Alberta is generally restricted to members of the University of Alberta community and to users of the Library's physical facilities. It is the responsibility of each user to ensure that he or she uses this product for individual, non-commercial educational or research purposes only, and does not systematically download or retain substantial portions of information.</p>  
					<p>Specific terms and conditions of use for this title are listed below.<br /> If no terms are defined, please refer to the <a href='https://www.ualberta.ca/faculty-and-staff/copyright/student-staff-guide/ualberta-policies-procedures/fair-dealing-guidelines.html'>UA Fair Dealing Guidelines.</a></p>
					";


					//loop through each distinct displayable expression type
					foreach ($uniqueExpressionTypeArray as $expressionTypeID){
						$expressionType = new ExpressionType(new NamedArguments(array('primaryKey' => $expressionTypeID)));
                                                //Custom Code by Jeremy March 26 2011
                                                //Seperate the ILL expression ($expressionTypeID: 2) from the rest of the links
                                                if ($expressionTypeID==2){
                                                    $interLibraryLoadHTML .= "<div>Staff Use</div>
                                                                  <a href='?issn=" . $issn . "&isbn=" . $isbn . "&typeID=" . $expressionType->expressionTypeID . "'>" . ($expressionType->shortName) . "</a>
                                                        
                                                                        ";
                                                }else{
                                                    $displayHTML .= "<a class='lists' href='?issn=" . $issn . "&isbn=" . $isbn . "&typeID=" . $expressionType->expressionTypeID . "'>" . ($expressionType->shortName) . "</a>";
                                                }
					}
                                        //Custom Code by Jeremy March 26 2011
                                        //added . $interLibraryLoadHTML;
					$displayHTML .= "<br />" . $interLibraryLoadHTML;
				}

			}

		//end target count
		}

	}catch(Exception $e){
		$displayHTML = $e->getMessage() . "  Please verify your information in the configuration.ini file and try again.";
	}

//end if isbn/issn passed in
}



?>


<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Conditions of Use</title>
<link rel="stylesheet" href="css/style.css" type="text/css" />
<script type="text/javascript" src="js/plugins/jquery.js"></script>
<script type="text/javascript" src="js/index.js"></script>
</head>
<body>
	<div class="top_bar"></div>
<a href="http://library.ualberta.ca"><h1><em>University of Alberta</em></h1></a>
<center>
	<div class="content">
<?php echo html_entity_decode($displayHTML); ?>

</div>

</center>
<div class="footer_wrap"> 
		
			<div class="uafooter-wrap"> 
			<div class="uafooter"> 
		
			</div> 
			</div> 
		</div>
</body>
</html>















