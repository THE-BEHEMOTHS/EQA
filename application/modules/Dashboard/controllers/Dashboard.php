<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends DashboardController {
	public function __construct(){
		parent::__construct();

		$this->load->library('table');
        $this->load->config('table');
		$this->load->model('dashboard_m');
		$this->load->module('Participant');
		$this->load->model('M_PTRound');
		$this->load->module('Program');
		$this->load->model('Program_m');

	}
	
	public function index()
	{	
		$data = [];

		$type = $this->session->userdata('type');
		$this->assets->addCss('css/main.css');
		$this->assets->addJs('js/main.js');

		// $view = "admin_dashboard";
		
		if($type == 'participant'){
			$this->db->where('status','active');
			$get = $this->db->get('pt_round_v')->row();

			if($get == null){
				$locking = 0;
			}else{
				$ongoing_check = $this->db->get_where('pt_round_v', ['type'=>'ongoing','status' => 'active'])->row();

				if($ongoing_check){
					$ongoing_pt = $ongoing_check->uuid;
				}else{
					$ongoing_pt = 0;
				}
		
				if($ongoing_pt){
					$checklocking = $this->M_PTRound->allowPTRound($ongoing_pt, $this->session->userdata('uuid'));

					if($checklocking == null){
						$locking = 0;
					}else{
						$locking = $checklocking->receipt;
					}
				}else{
					$locking = 0;
				}
			}

			$participant = $this->M_Participant->findParticipantByIdentifier('uuid', $this->session->userdata('uuid'));

			
			$facility = $this->db->get_where('facility_v', ['facility_id' => $participant->participant_facility])->row();

			$capa_check = $this->db->get_where('messages', ['to_facility' => $facility->facility_code])->row();

			if($capa_check){
				$capa = 1;
			}else{
				$capa = 0;
			}
			
			// echo "<pre>";print_r($facility);echo "</pre>";die();
			$this->load->model('participant/M_Participant');
			$view = "dashboard_v";
			$this->assets
				->addJs('js/Chart.min.js')
                ->addJs('js/chartsjs-plugin-data-labels.js')
                ->addJs('js/Chart.PieceLabel.js');
			$this->assets->setJavascript('Dashboard/dashboard_js');
			$data = [
				'receipt'   		=>  $locking,
				'dashboard_data'	=>	$this->getParticipantDashboardData($this->session->userdata('uuid')),
				'participant'		=>	$participant,
				'facility_id'		=>	$participant->participant_facility,
				'capa_check'		=>	$capa
			];
		}elseif($type == "admin"){
			$view = "admin_dashboard";
			$this->assets->setJavascript('PTRounds/calendar_js');

			$round = $this->dashboard_m->getCurrentRound();

			if($round){
				$round_uuid = $round->uuid;
			}else{
				$round_uuid = '';
			}

			

			$stats = $this->getDashboardStats();
			$data = [
                'pending_participants'    =>  $this->dashboard_m->pendingParticipants(),
                'new_equipments'    =>  $this->dashboard_m->newEquipments(),
                'stats'			=>	$stats,
                'round_uuid' => $round_uuid
            ];
		}else if($type == "qareviewer"){
			$this->db->where('status','active');
			$this->db->where('type', 'ongoing');
			$round = $this->db->get('pt_round_v')->row();

			if($round){

			}
            $view = "qa_dashboard";
            $data = [
            	'pt_round'	=>	$round
            ];

            if($round){
            	$data['round'] = $round->id;
            }
        }else if($type == "program"){
        	redirect('Program/', 'refresh');
        }

        // echo "<pre>";print_r($data);echo "</pre>";die();
        $this->assets
				->addJs('dashboard/js/libs/moment.min.js')
				->addJs('dashboard/js/libs/fullcalendar.min.js')
				->addJs('dashboard/js/libs/gcal.js');

		
		$this->template->setPageTitle('EQA Dashboard')->setPartial($view,$data)->adminTemplate();
	}

	private function getDashboardStats(){
		$this->db->where('status', 'active');
		$this->db->where('type', 'ongoing');
		$pt_round = $this->db->get('pt_round_v')->row();

		$stats = new StdClass;
		$stats->pt_round = null;
		$stats->readiness_submissions = 0;
		$stats->received_panels = 0;
		$stats->not_received_panels = 0;
		$stats->pending_review = 0;
		$stats->no_response = 0;
		$stats->completed_and_revied = 0;
		$stats->not_completed = 0;

		if ($pt_round) {
			$stats->pt_round = $pt_round->pt_round_no;
			$dashboard_stats = $this->dashboard_m->getDashboardStats($pt_round->uuid);
			$stats->readiness_submissions = $dashboard_stats->readiness_submitted;
			$stats->received_panels = $dashboard_stats->panels_received;
			$stats->not_received_panels = $dashboard_stats->panels_not_received;
		}

		return $stats;
	}


	public function viewMessages(){
		$data = [];
        $title = "My Messages";
        // $equipment_count = $this->db->count_all('equipment');

        
        	$data = [
                'table_view'    =>  $this->createMessagesTable()
            ];
        

        $this->assets
                ->addCss("plugin/sweetalert/sweetalert.css");
        $this->assets
                ->addJs("dashboard/js/libs/jquery.dataTables.min.js")
                ->addJs("dashboard/js/libs/dataTables.bootstrap4.min.js")
                ->addJs("plugin/sweetalert/sweetalert.min.js");
        $this->assets->setJavascript('Dashboard/message_js');
        $this->template
                ->setPageTitle($title)
                ->setPartial('Dashboard/messages_v', $data)
                ->adminTemplate();
	}


    function myMessage($message_uuid){
    	$this->db->where('uuid', $message_uuid);
        $message = $this->db->get('messages_v')->row();
        //echo '<pre>';print_r($message);echo '</pre>';die();

        if($message){
        	$this->db->set('status', 1);

            $this->db->where('uuid', $message_uuid);

            if($this->db->update('messages')){
                $this->session->set_flashdata('success', "Message marked as read");
            }
        }
        
        $data = [
            'from'          =>  $message->from,
            'email'        =>  $message->email,
            'subject'        =>  $message->subject,
            'message'              =>  $message->message,
            'date_sent'          =>  $message->date_sent
        ];

        $this->assets
                ->addJs("dashboard/js/libs/jquery.dataTables.min.js")
                ->addJs("dashboard/js/libs/dataTables.bootstrap4.min.js");
        $this->template
                ->setPartial('Dashboard/my_message', $data)
                ->setPageTitle('My Message')
                ->adminTemplate();
    }

    function RemoveMessage($message_uuid){
    	// echo "<pre>";print_r($message_uuid);echo "</pre>";die();
    	$this->db->set('deleted', 1);

            $this->db->where('uuid', $message_uuid);

            if($this->db->update('messages')){
                $this->session->set_flashdata('success', "Successfully removed the message");
                echo "success";
            }else{
                $this->session->set_flashdata('error', "There was a problem removing the message. Please try again");
                echo "fail";
            }

            // redirect('Dashboard/viewMessages/');
    }


    private function getParticipantDashboardData($participant_uuid){
		$dashboard_data = new StdClass();
		$dashboard_data->current = "";
		$this->db->where('type', 'ongoing');
		$this->db->where('status', 'active');
		$query = $this->db->get('pt_round_v');

		$dashboard_data->calendar_legend = $this->createCalendarLegend();

		if($query->num_rows() == 1){
			$dashboard_data->rounds = $query->num_rows();
			$pt_round = $query->row();
			$readiness = $this->db->get_where('participant_readiness', ['participant_id'=>$participant_uuid, 'pt_round_no' => $pt_round->uuid])->row();
			$dashboard_data->pt_round = $pt_round;

			$today =  date('Y-m-d');

			$this->db->select('c.item_name, c.colors, ptc.date_from, ptc.date_to');
			$this->db->from('pt_calendar ptc');
			$this->db->join('calendar_items c', 'c.id = ptc.calendar_item_id');
			$this->db->join('pt_round pr', 'pr.id = ptc.pt_round_id');
			$this->db->where('pr.uuid', $pt_round->uuid);
			$this->db->where("ptc.date_from <=", $today);
			$this->db->where("ptc.date_to >=", $today);

			$calendar_query = $this->db->get();
			$dashboard_data->calendar_current = new StdClass();
			$dashboard_data->calendar_current->color = "";
			if($calendar_query->num_rows() > 1){
				$calendar_current_list = "<ul>";
				foreach ($calendar_query->result() as $value) {
					$days_left = $this->calculateDateDifference($value->date_to);

					$calendar_current_list .= "<li>$value->item_name: $days_left Left</li>";
				}
				$calendar_current_list .= "</ul>";
				$dashboard_data->calendar_current->name = $calendar_current_list;
			}elseif($calendar_query->num_rows() == 1){
				$item = $calendar_query->row();
				$days_left = $this->calculateDateDifference($item->date_to);
				$dashboard_data->calendar_current->name = $item->item_name . ": $days_left Left";
				$dashboard_data->calendar_current->color = $item->colors;
			}else{
				$dashboard_data->calendar_current = "No Current Item";
			}

			if($readiness){
				$this->db->where('readiness_uuid', $readiness->uuid);
				$this->db->where('pt_round_uuid', $pt_round->uuid);
				$participant_readiness = $this->db->get('pt_ready_participants')->row();
				$dashboard_data->readiness = $participant_readiness;

				// echo "<pre>";print_r($participant_readiness);echo"</pre>";die();

				if($participant_readiness){
					if($participant_readiness->status_code == 2){
						$dashboard_data->current = "enroute";
					}elseif($participant_readiness->status_code == 3){
						if($participant_readiness->receipt == 1){

							

							$this->db->select('DISTINCT(participant_id)');
							$this->db->where('round_id', $pt_round->id);
							$this->db->where('participant_id', $participant_readiness->p_id);
							$participant_responsive = $this->db->get('pt_participant_review_v')->row();

							if(!($participant_responsive) && ((strtotime($dashboard_data->pt_round->to) > date('Y-m-d')))){
								$dashboard_data->current = "non_responsive";
							}else{
								$dashboard_data->current = "pt_round_submission";
							}

						}else{
							$dashboard_data->current = "bad_panel";
						}
					}
				}

				
			}else{
				$dashboard_data->current = "readiness";
			}
			
		}else{
			$dashboard_data->rounds = $query->num_rows();
		}


		return $dashboard_data;
	}	

	function calculateDateDifference($date1, $date2 = NULL, $format = NULL){
		$date_time_1 = date_create($date1);
		$date_time_2 = ($date2 == NULL) ? date_create(date('Y-m-d')) : date_create($date2);

		$interval = date_diff($date_time_1, $date_time_2);
		$format = ($format == NULL) ? '%a Days' : $format;
		$difference = $interval->format($format);

		return $difference;
	}

	private function createCalendarLegend(){
		$calendar_items = $this->db->get('calendar_items')->result();
		$calendar_legend = "";
		if ($calendar_items) {
			foreach ($calendar_items as $calendar_item) {
				$calendar_legend .= "<div class = 'm-1 clearfix'>
				<div class = 'pull-left' style = 'width: 30px;height:30px;background-color: {$calendar_item->colors};opacity: .3;'></div>
				<p class = 'pull-left ml-1'>{$calendar_item->item_name}</p>
				</div>";
			}
		}
		
		return $calendar_legend;
	}


	public function PassFailGraph($facility_id){
        $labels = $graph_data = $datasets = $data = array();
        $participants = $pass = $fail = $pass_rate = 0;
        $counter = $unsatisfactory = $satisfactory = $disqualified = $unable = $non_responsive = $partcount = $accept = $unaccept = $passed = $failed = 0;

        $backgroundColor = ['rgba(52,152,219,0.5)','rgba(46,204,113,0.5)','rgba(211,84,0,0.5)','rgba(231,76,60,0.5)','rgba(127,140,141,0.5)','rgba(241,196,15,0.5)','rgba(52,73,94,0.5)'
        ];

        $borderColor = ['rgba(52,152,219,0.8)','rgba(46,204,113,0.8)','rgba(211,84,0,0.8)','rgba(231,76,60,0.8)','rgba(127,140,141,0.8)','rgba(241,196,15,0.8)','rgba(52,73,94,0.8)'
        ];

        $highlightFill = ['rgba(52,152,219,0.75)','rgba(46,204,113,0.75)','rgba(211,84,0,0.75)','rgba(231,76,60,0.75)','rgba(127,140,141,0.75)','rgba(241,196,15,0.75)','rgba(52,73,94,0.75)'
        ];

        $highlightStroke = ['rgba(52,152,219,1)','rgba(46,204,113,1)','rgba(211,84,0,1)','rgba(231,76,60,1)','rgba(127,140,141,1)','rgba(241,196,15,1)','rgba(52,73,94,1)'
        ];

        $rounds = $this->Program_m->getLatestRounds();

        $pass = [
            'label'         =>  'Pass',
            'backgroundColor' => 'rgba(46,204,113,0.5)',
            'borderColor' => 'rgba(46,204,113,0.8)',
            'highlightFill' => 'rgba(46,204,113,0.75)',
            'highlightStroke' => 'rgba(46,204,113,1)'
        ];

        $fail = [
            'label'         =>  'Fail',
            'backgroundColor' => 'rgba(211,84,0,0.5)',
            'borderColor' => 'rgba(211,84,0,0.8)',
            'highlightFill' => 'rgba(211,84,0,0.75)',
            'highlightStroke' => 'rgba(211,84,0,1)'
        ];


        if($rounds){
            foreach ($rounds as $round) {
                $data = [];
                $partcount = $counter = 0;

                $round_id = $this->db->get_where('pt_round', ['uuid' => $round->uuid])->row()->id;
                $samples = $this->db->get_where('pt_samples', ['pt_round_id' =>  $round_id])->result();
                if($facility_id){
                    $county_id = $this->db->get_where('facility_v', ['facility_id' => $facility_id])->row()->county_id;
                }
                $participants = $this->Program_m->getReadyParticipants($round_id, $county_id, $facility_id);
                $equipments = $this->Program_m->Equipments();

                // echo "<pre>";print_r($participants);die;

                if($participants){
                
	                foreach ($participants as $participant) {
                        $partcount++;
                        $novalue = $sampcount = $acceptable = $unacceptable = $novalue = $sampcount = 0;

                        foreach ($samples as $sample) {
                            $sampcount++;

                            $cd4_values = $this->Program_m->getRoundResults($round_id, $participant->equipment_id, $sample->id);

                            if($cd4_values){

                                $upper_limit = $cd4_values->cd4_absolute_upper_limit;
                                $lower_limit = $cd4_values->cd4_absolute_lower_limit;
                            }else{
                                $upper_limit = 0;
                                $lower_limit = 0;
                            } 

                            $part_cd4 = $this->Program_m->absoluteValue($round_id,$participant->equipment_id,$sample->id,$participant->participant_id);

                            if($part_cd4){
			                    if($part_cd4->cd4_absolute >= $lower_limit && $part_cd4->cd4_absolute <= $upper_limit){
			                        $acceptable++;
			                    }    
			                } 
                        } 

                        if($acceptable == $sampcount) {
                            $passed++;
                        }
	                    
	                }

	                $failed = $partcount - $passed;

	            }else{
	            	$passed = 0;
	            	$failed = 0;
	            }
                

                $labels[] = $round->pt_round_no;
  
                $pass['data'][] = $passed;
                $fail['data'][] = $failed;
                
            }
        }

        $graph_data['labels'] = $labels;
        $graph_data['datasets'] = [$pass, $fail];

        

        return $this->output->set_content_type('application/json')->set_output(json_encode($graph_data));
    }


    public function PassFailRateGraph($facility_id){
        $labels = $graph_data = $datasets = $data = array();
        $participants = $pass = $fail = $pass_rate = 0;
        $counter = $unsatisfactory = $satisfactory = $disqualified = $unable = $non_responsive = $partcount = $accept = $unaccept = $passed = $failed = 0;

        $backgroundColor = ['rgba(52,152,219,0.5)','rgba(46,204,113,0.5)','rgba(211,84,0,0.5)','rgba(231,76,60,0.5)','rgba(127,140,141,0.5)','rgba(241,196,15,0.5)','rgba(52,73,94,0.5)'
        ];

        $borderColor = ['rgba(52,152,219,0.8)','rgba(46,204,113,0.8)','rgba(211,84,0,0.8)','rgba(231,76,60,0.8)','rgba(127,140,141,0.8)','rgba(241,196,15,0.8)','rgba(52,73,94,0.8)'
        ];

        $highlightFill = ['rgba(52,152,219,0.75)','rgba(46,204,113,0.75)','rgba(211,84,0,0.75)','rgba(231,76,60,0.75)','rgba(127,140,141,0.75)','rgba(241,196,15,0.75)','rgba(52,73,94,0.75)'
        ];

        $highlightStroke = ['rgba(52,152,219,1)','rgba(46,204,113,1)','rgba(211,84,0,1)','rgba(231,76,60,1)','rgba(127,140,141,1)','rgba(241,196,15,1)','rgba(52,73,94,1)'
        ];

        $rounds = $this->Program_m->getLatestRounds();

        $no_participants = [
            'label'         =>  'Pass Rate (%)',
            'borderColor' => 'rgba(52,152,219,0.8)',
            'highlightFill' => 'rgba(52,152,219,0.75)',
            'highlightStroke' => 'rgba(52,152,219,1)',
            'yAxisID' => 'y-axis-1',
            'type' => 'line'
        ];

        if($rounds){
            foreach ($rounds as $round) {
                $data = [];
                $partcount = $counter = 0;



                $round_id = $this->db->get_where('pt_round', ['uuid' => $round->uuid])->row()->id;
                $samples = $this->db->get_where('pt_samples', ['pt_round_id' =>  $round_id])->result();

                if($facility_id){
                    $county_id = $this->db->get_where('facility_v', ['facility_id' => $facility_id])->row()->county_id;
                }

                $participants = $this->Program_m->getReadyParticipants($round_id, $county_id, $facility_id);

                
	            // echo "<pre>";print_r($no_of_participants);die;
		        if($participants){

		            foreach ($participants as $participant) {
		                    $partcount ++;
		                    $novalue = $sampcount = $acceptable = $unacceptable = $novalue = $sampcount = 0;


		                    foreach ($samples as $sample) {
		                        $sampcount++;

		                        $cd4_values = $this->Program_m->getRoundResults($round_id, $participant->equipment_id, $sample->id);

		                        if($cd4_values){

		                            $upper_limit = $cd4_values->cd4_absolute_upper_limit;
		                            $lower_limit = $cd4_values->cd4_absolute_lower_limit;
		                        }else{
		                            $upper_limit = 0;
		                            $lower_limit = 0;
		                        } 

		                        $part_cd4 = $this->Program_m->absoluteValue($round_id,$participant->equipment_id,$sample->id,$participant->participant_id);

		                        if($part_cd4->cd4_absolute >= $lower_limit && $part_cd4->cd4_absolute <= $upper_limit){
	                                $acceptable++;    
	                            }
		                    } 

		                    if($acceptable == $sampcount) {
		                        $passed++;
		                    }
		            }

		            // $no_of_participants = $this->Program_m->ParticipatingParticipants($round->uuid, $county_id, $facility_id)->participants;

		            $pass_rate = round((($passed / $partcount) * 100), 2);

		        }else{
		        	$pass_rate = 0;
		        }


                $labels[] = $round->pt_round_no;

                $no_participants['data'][] = $pass_rate;
   
            }
        }

        // $no_participants['yAxisID'] = 'y-axis-2';

        $graph_data['labels'] = $labels;
        $graph_data['datasets'] = [$no_participants];

        // echo "<pre>";print_r($graph_data);die;

        return $this->output->set_content_type('application/json')->set_output(json_encode($graph_data));
    }







}

/* End of file Dashboard.php */
/* Location: ./application/modules/Home/controllers/Dashboard.php */
