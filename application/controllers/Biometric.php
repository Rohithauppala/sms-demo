<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Biometric extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();

        $this->load->helper('json_output');
        $this->load->library('mailsmsconf');
        $this->load->model(['setting_model', 'student_model', 'stuattendence_model', 'studentAttendaceSetting_model', 'staffAttendaceSetting_model']);
    }

    public function index()
    {
        $method = $this->input->server('REQUEST_METHOD');
        if ($method != 'POST') {
            json_output(400, array('status' => 400, 'message' => 'Bad request.'));
        } else {

            $attendence_param = file_get_contents('php://input');
            $params           = json_decode(file_get_contents('php://input'), true);
            $settings         = $this->setting_model->getSchoolDetail();

            if ($settings->biometric) {
                $total_devices = array_map('trim', explode(",", $settings->biometric_device));
                if (!empty($params)) {

                    if ($params['serial_number']) {

                        if (in_array($params['serial_number'], $total_devices)) {
                            $record_data = $this->student_model->biometric_attendance($params['user_id']);
                         
 
                            if ($record_data) {
                                if ($record_data->table_type == "staff") {
                                    $insert_record = array(
                                        'date'                  => date('Y-m-d', strtotime($params['t'])),
                                        'staff_id'               => $record_data->id,
                                        'staff_attendance_type_id'    => '',
                                        'biometric_attendence'  => 1,
                                        'remark'                => '',
                                        'created_at'            => $params['t'],
                                        'biometric_device_data' => $attendence_param,
                                    );
                                    $insert_result = $this->staffattendancemodel->onlineattendence($insert_record, $record_data->staff_role);
                                } elseif ($record_data->table_type == "student") {
                                    $insert_record = array(
                                        'date'                  => date('Y-m-d', strtotime($params['t'])),
                                        'student_session_id'    => $record_data->id,
                                        'attendence_type_id'    => '',
                                        'biometric_attendence'  => 1,
                                        'remark'                => '',
                                        'created_at'            => $params['t'],
                                        'biometric_device_data' => $attendence_param,
                                    );
                                    $insert_result = $this->stuattendence_model->onlineattendence($insert_record, $record_data->class_section_id);
									   
                                }

                                if ($insert_result == 1) {
                                    json_output(200, array('status' => 200, 'message' => 'Attendance submitted'));
                                } elseif($insert_result == 2) {
                                    json_output(200, array('status' => 200, 'message' => 'Attendance setting not configured, or contact to admin'));
                                }elseif($insert_result == 0) {
                                    json_output(200, array('status' => 200, 'message' => 'Attendance already submitted'));
                                }
                            }
                        }
                    }
                } else {
                    json_output(200, array('status' => 200, 'message' => 'No Parameter Found'));
                }
            }
        }
    }
}
