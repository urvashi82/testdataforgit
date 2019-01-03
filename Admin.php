<?php

//

defined('BASEPATH') OR exit('No direct script access allowed');

class Admin extends CI_Controller {

    protected $basic_notification = array();
    protected $user_notification = array();
    protected $vendor_notification = array();

    public function __construct() {
        parent::__construct();
        $this->load->model('adminmodel');
        $this->load->model('apimodel');
        $this->load->library('CommonLib');
        $this->load->library('pushnotification');

        $this->basic_notification = array(array('notifyId' => 1, 'notifyLabel' => 'Play sound on notification', 'isEnabled' => true), array('notifyId' => 2, 'notifyLabel' => 'Vibrate on notification', 'isEnabled' => true), array('notifyId' => 3, 'notifyLabel' => 'Pulse light for notification', 'isEnabled' => true));
        $this->user_notification = array(array('notifyId' => 4, 'notifyLabel' => 'Vendor has confirmed your request', 'isEnabled' => true), array('notifyId' => 5, 'notifyLabel' => 'Vendor is on route to your location', 'isEnabled' => true), array('notifyId' => 6, 'notifyLabel' => 'Vendor has arrived and your service has been processed', 'isEnabled' => true), array('notifyId' => 7, 'notifyLabel' => 'Service has been completed', 'isEnabled' => true), array('notifyId' => 8, 'notifyLabel' => 'Vendor has cancelled request', 'isEnabled' => true), array('notifyId' => 9, 'notifyLabel' => 'I can review my recent purchases', 'isEnabled' => true), array('notifyId' => 10, 'notifyLabel' => 'New app features are available', 'isEnabled' => true), array('notifyId' => 11, 'notifyLabel' => 'There are items left in my cart', 'isEnabled' => true), array('notifyId' => 12, 'notifyLabel' => 'New updates in my favourite service', 'isEnabled' => true));

        $this->vendor_notification = array(array('notifyId' => 4, 'notifyLabel' => 'User has made request', 'isEnabled' => true), array('notifyId' => 5, 'notifyLabel' => 'User has made payment for confirmed service', 'isEnabled' => true), array('notifyId' => 6, 'notifyLabel' => 'User has canceled request for confirmed service', 'isEnabled' => true), array('notifyId' => 7, 'notifyLabel' => 'New app features are available', 'isEnabled' => true));
    }

    function index() {
        if (empty($this->session->userdata()['admindata'])) {
            redirect(base_url("admin/login"));
        } else {
            redirect(base_url("admin/dashboard"));
        }
    }

    public function login() {
        header("Cache-Control: no cache");
        session_cache_limiter("private_no_expire");

        if (empty($this->session->userdata()['admindata'])) {
            $this->load->view("admin/login_view");
        } else {
            redirect(base_url("admin/dashboard"));
        }
    }

    public function checklogin() {
        try {
            $postData = $this->input->post(NULL, true);
            if (!empty($postData)) {
                $user = $this->adminmodel->isValidUser($postData);
                if ($user) {
                    $user['_id'] = (string) $user['_id'];
                    $this->session->set_userdata('admindata', $user);
                    $this->Message("Login Successful", true);
                } else {
                    $this->Message("Please check email address or password");
                }
            } else {
                $this->Message("Invalid request");
            }
        } catch (Exception $e) {
            $msg = $e->getMessage();
            $this->Message($msg);
        }
    }

    private function Message($message, $result = false) {
        header("Content-Type:application/json");
        echo json_encode([
            'Result' => $result,
            'Message' => $message
        ]);
        die;
    }

    public function logout() {
        $this->session->unset_userdata('admindata');
        redirect(base_url('admin/login'));
    }

    private function isLoggedIn() {
        if (empty($this->session->userdata()['admindata'])) {
            redirect(base_url("admin/login"));
            die();
        } else {
            return true;
        }
    }

    public function dashboard() {
        if ($this->isLoggedIn()) {

            $header['active_page'] = "dashboard";

            $this->load->view('admin/header', $header);

            $data['vendors'] = $this->adminmodel->getTotalUsers("", 2);
            $data['buyers'] = $this->adminmodel->getTotalUsers("", 1);
            $data['pending_orders'] = $this->adminmodel->get_total_orders_by_status([0, 1, 2, 3, 4]);
            $data['complete_orders'] = $this->adminmodel->getTotalOrders('', [5], $filter = ['isRemoved' => false]);
            $data['cancelled_orders'] = $this->adminmodel->get_total_orders_by_status([6]);

            $this->load->view('admin/dashboard_view', $data);
            $this->load->view('admin/footer');
        }
    }

    function configurations() {
        if ($this->isLoggedIn()) {

            $header['active_page'] = "configurations";

            $this->load->view('admin/header', $header);
            $data['configurations'] = $this->adminmodel->get_configurations();
            $data['api_configuration'] = $this->adminmodel->get_api_configurations();
            $this->load->view('admin/setting_view', $data);
            $this->load->view('admin/footer');
        }
    }

    function save_configurations() {
        try {
            if ($this->isLoggedIn()) {
                $postData = array_map('trim', $this->input->post(NULL, true));
                ;
                if (!empty($postData)) {
                    $result = $this->adminmodel->update_configurations($postData);
                    if ($result) {
                        $this->Message("Configuration updated successfully", true);
                    } else {
                        $this->Message("Failed to update");
                    }
                } else {
                    $this->Message("Invalid request");
                }
            } else {
                $this->Message("Invalid request");
            }
        } catch (Exception $e) {
            $msg = $e->getMessage();
            $this->Message($msg);
        }
    }

    function save_versions() {
        try {
            if ($this->isLoggedIn()) {
                $postData = array_map('trim', $this->input->post(NULL, true));
                $checkUpdate = false;
                if (isset($postData['checkUpdate'])) {
                    $checkUpdate = true;
                }
                $postData['checkUpdate'] = $checkUpdate;
                $result = $this->adminmodel->update_versions($postData);
                if ($result) {
                    $this->Message("Configuration updated successfully", true);
                } else {
                    $this->Message("Failed to update");
                }
            } else {
                $this->Message("Invalid request");
            }
        } catch (Exception $e) {
            $msg = $e->getMessage();
            $this->Message($msg);
        }
    }

    /*
      Done by : 1081
      Description : This function to get master category view
      Createa at : 12/08/2017
     */

    public function sub_categories() {
        if ($this->isLoggedIn()) {

            $header['active_page'] = "master_cat";

            $this->load->view('admin/header', $header);

            //$totalCategory = $this->adminmodel->getTotalMasterCategory();
            $masterCategories = $this->adminmodel->getMasterCategoryList();
            //echo "<pre>";print_r($masterCategories);
            $data['masterCategories'] = $masterCategories;

            $this->load->view('admin/sub_sub_categories', $data);
            $this->load->view('admin/sub_sub_categories_js', array());
            $this->load->view('admin/footer');
        }
    }

    /*
      Done by : 1081
      Description : This function to get master category data using ajax
      Createa at : 12/08/2017
     */

    public function get_sub_subcategory_data() {
        $dataTable = $this->input->post(NULL, true);
        //echo "<pre>";print_r($dataTable);
        $draw = $dataTable['draw'];
        $start = $dataTable['start'];
        $length = $dataTable['length'];

        $orderData = $dataTable['order'];
        $orderKeyIndex = $orderData[0]['column'];
        $direction = $orderData[0]['dir'];
        $search = $dataTable['search']['value'];

        $keys = array('_id', 'subCatImage', 'subCatName', 'masterCatName', 'action', 'isActive', 'createdDateTime');

        $totalCategory = $this->adminmodel->getTotalSubSubcategory($search);

        $directionInt = 1;
        if ($direction == 'desc') {
            $directionInt = -1;
        }

        if ($totalCategory > 0) {
            $subCategories = $this->adminmodel->getSubSubcategories($start, $length, $search, $keys[$orderKeyIndex], $directionInt);
            $data = [];
            foreach ($subCategories as $key => $eachSubCategory) {
                $eachSubCategory = (array) $eachSubCategory;

                //echo "<pre>";print_r($eachSubCategory);
                //exit;
                $subcatId = (string) $eachSubCategory['_id'];
                if ($eachSubCategory['isActive']) {
                    $icon_class = "fa fa-eye text-green";
                    $to_do = false;
                    $display_title = "Deactivate sub category";
                } else {
                    $icon_class = "fa fa-eye-slash text-danger";
                    $to_do = true;
                    $display_title = "Activate sub category";
                }

                $img = base_url() . 'uploads/category_images/placeholder.png';
                $extraClass = 'placeholder';
                if ($eachSubCategory['subCatImage'] != '' && file_exists(FCPATH . "uploads/category_images/" . $eachSubCategory['subCatImage'])) {
                    $img = base_url() . 'uploads/category_images/' . $eachSubCategory['subCatImage'];
                    $extraClass = '';
                }

                $data[] = array($subcatId, '<div><img onerror=\'this.src="' . base_url('uploads/category_images/placeholder.png') . '"\' src="' . $img . '" width="100%" class="img-responsive img-thumbnail ' . $extraClass . '"></div>', $eachSubCategory['subCatName'], $eachSubCategory['masterCatName'], '<a class="edit_sub_category action_icon" catId="' . $subcatId . '" title="Edit sub category"><i class="fa fa-edit"></i></a><a class="delete_category action_icon" catId="' . $subcatId . '" title="Delete sub category"><i class="fa fa-remove text-danger"></i></a><a class="toggle_activation_category action_icon" catId="' . $subcatId . '" title="' . $display_title . '" toggle_Data = "' . $to_do . '"><i class="' . $icon_class . '"></i></a>', $eachSubCategory['isActive'], $eachSubCategory['createdDateTime']);
            }
            echo json_encode(array('draw' => $draw, 'recordsTotal' => $totalCategory, 'recordsFiltered' => $totalCategory, 'data' => $data));
        } else {
            echo json_encode(array('draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => array()));
        }
    }

    /*
      Done by : 1081
      Description : This function to add or edit sub cateogry data
      Createa at : 12/08/2017
     */

    public function performSubSubcategory() {
        try {
            $subCatData = json_decode($this->input->raw_input_stream, true);
            if ($this->isLoggedIn()) {
                if ($subCatData['catId'] != '') {
                    $ImageString = $subCatData['profileImageString'];
                    unset($subCatData['profileImageString']);

                    if ($ImageString != "") {

                        $new_file_name = time() . ".jpg";

                        $subCatData['subCatImage'] = $new_file_name;

                        $file_upload_path = "./uploads/category_images/" . $new_file_name;

                        file_put_contents($file_upload_path, base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $ImageString)));
                    }

                    $subCatData['masterCat'] = new MongoDB\BSON\ObjectID($subCatData['masterCat']);
                    $updateCat = $this->adminmodel->editNewSubCategory($subCatData);
                    if ($updateCat) {
                        echo json_encode(array("Result" => true, 'Message' => 'Sub category updated succesfully.'));
                    } else {
                        echo json_encode(array("Result" => false, 'Message' => 'Unable to save update subcategory data'));
                    }
                } else {
                    // insert sub category
                    unset($subCatData['catId']);
                    $subCatData['masterCat'] = new MongoDB\BSON\ObjectID($subCatData['masterCat']);
                    $ImageString = $subCatData['profileImageString'];
                    unset($subCatData['profileImageString']);

                    if ($ImageString != "") {

                        $new_file_name = time() . ".jpg";

                        $subCatData['subCatImage'] = $new_file_name;

                        $file_upload_path = "./uploads/category_images/" . $new_file_name;

                        file_put_contents($file_upload_path, base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $ImageString)));
                    } else {
                        $subCatData['subCatImage'] = "";
                    }

                    $insertNew = $this->adminmodel->addNewSubCategory($subCatData);
                    if ($insertNew) {
                        echo json_encode(array("Result" => true, 'Message' => 'New sub category inserted succesfully.'));
                    } else {
                        echo json_encode(array("Result" => false, 'Message' => 'Unable to save category data'));
                    }
                }
            }
        } catch (MongoDB\Driver\Exception\Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        } catch (Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        }
    }

    /*
      Done by : 1081
      Description : This function to delete master category data
      Createa at : 14/08/2017
     */

    public function deleteMasterCategory() {
        try {
            $masterCatData = json_decode($this->input->raw_input_stream, true);
            if ($this->isLoggedIn()) {
                $deleteFlag = $this->adminmodel->deleteCategory($masterCatData['catId'], $masterCatData['collectionName']);
                if ($deleteFlag) {
                    echo json_encode(array("Result" => true, 'Message' => 'Succesfully removed this category'));
                } else {
                    echo json_encode(array("Result" => false, 'Message' => 'Unable to remove this category'));
                }
            }
        } catch (MongoDB\Driver\Exception\Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        } catch (Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        }
    }

    public function deleteSalesPerson() {
        try {
            $salesPersonData = json_decode($this->input->raw_input_stream, true);
            if ($this->isLoggedIn()) {
                $deleteFlag = $this->adminmodel->deleteSalesPerson($salesPersonData['salesPersonId']);
                if ($deleteFlag) {
                    echo json_encode(array("Result" => true, 'Message' => 'Succesfully removed this category'));
                } else {
                    echo json_encode(array("Result" => false, 'Message' => 'Unable to remove this category'));
                }
            }
        } catch (MongoDB\Driver\Exception\Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        } catch (Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        }
    }

    /*
      Done by : 1081
      Description : This function to active/deactive master category
      Createa at : 14/08/2017
     */

    public function toggleCategory() {
        try {
            $masterCatData = json_decode($this->input->raw_input_stream, true);
            if ($this->isLoggedIn()) {
                $deleteFlag = $this->adminmodel->toggleCategory($masterCatData['catId'], $masterCatData['isActive'], $masterCatData['collectionName']);
                if ($deleteFlag) {
                    echo json_encode(array("Result" => true, 'Message' => 'Succesfully toggle this category'));
                } else {
                    echo json_encode(array("Result" => false, 'Message' => 'Unable to toggle this category'));
                }
            }
        } catch (MongoDB\Driver\Exception\Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        } catch (Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        }
    }

    public function toggleSalesPerson() {
        try {
            $salesPersonData = json_decode($this->input->raw_input_stream, true);
            if ($this->isLoggedIn()) {
                $deleteFlag = $this->adminmodel->toggleSalesPerson($salesPersonData['salesPersonId'], $salesPersonData['isActive']);

                //Previously it was such that if a salesperson is activated/deactivated then find user of similar
                //email in user table and deactivate and activate it
                /* if ($deleteFlag) {
                  $foundSalesPersonData = $this->adminmodel->getSingleSalesPersonData($salesPersonData['salesPersonId']);
                  $toggleFlag = false;
                  if (!empty($foundSalesPersonData)) {
                  $foundSalesPersonData = (array) $foundSalesPersonData[0];
                  $email = $foundSalesPersonData['email'];
                  $salesPerson = $this->apimodel->check_email_exists($email);
                  $salesPerson = (array) $salesPerson;
                  $id = (array) $salesPerson['_id'];
                  $toggleFlag = $this->adminmodel->toggleUser($id['oid'], $salesPersonData['isActive']);
                  }
                  } */
                if ($deleteFlag) {
                    echo json_encode(array("Result" => true, 'Message' => 'Succesfully toggle this salesperson'));
                } else {
                    echo json_encode(array("Result" => false, 'Message' => 'Unable to toggle this salesperson'));
                }
            }
        } catch (MongoDB\Driver\Exception\Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        } catch (Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        }
    }

    /*
      Done by : 1081
      Description : This function to get sub category view
      Createa at : 12/08/2017
     */

    public function categories() {
        if ($this->isLoggedIn()) {

            $header['active_page'] = "sub_cat";

            $this->load->view('admin/header', $header);

            /* $totalCategory = $this->adminmodel->getTotalMasterCategory();
              $masterCategories = $this->adminmodel->getMasterCategoryList();
              //echo "<pre>";print_r($masterCategories);
              $data['masterCategories'] = $masterCategories; */

            $this->load->view('admin/sub_categories', array());
            $this->load->view('admin/sub_categories_js', array());
            $this->load->view('admin/footer');
        }
    }

    /*
      Done by : 1081
      Description : This function to get sub category data using ajax
      Createa at : 12/08/2017
     */

    public function getSubCategorydata() {
        $dataTable = $this->input->post(NULL, true);
        //echo "<pre>";print_r($dataTable);
        $draw = $dataTable['draw'];
        $start = $dataTable['start'];
        $length = $dataTable['length'];

        $orderData = $dataTable['order'];
        $orderKeyIndex = $orderData[0]['column'];
        $direction = $orderData[0]['dir'];
        $search = $dataTable['search']['value'];

        $directionInt = 1;
        if ($direction == 'desc') {
            $directionInt = -1;
        }

        $keys = array('_id', 'image', 'catName', 'catDescription', 'action', 'active', 'createdDateTime');

        $totalSubCategory = $this->adminmodel->getTotalSubCategory($search);

        if ($totalSubCategory > 0) {
            $data = array();
            $subCategories = $this->adminmodel->getSubCategories($start, $length, $directionInt, $keys[$orderKeyIndex], $search);
            foreach ($subCategories as $key => $eachSubCategory) {
                $eachSubCategory = (array) $eachSubCategory;
                //echo "<pre>";print_r($eachSubCategory);
                //$masterCatId = (string) $eachSubCategory['masterCategoryId'];
                $catId = (string) $eachSubCategory['_id'];
                if ($eachSubCategory['isActive']) {
                    $icon_class = "fa fa-eye text-green";
                    $to_do = false;
                    $display_title = "Deactivate category";
                } else {
                    $icon_class = "fa fa-eye-slash text-danger";
                    $to_do = true;
                    $display_title = "Activate category";
                }
                $img = base_url() . 'uploads/category_images/placeholder.png';
                $extraClass = 'placeholder';
                
                if ($eachSubCategory['categoryImage'] != '' && file_exists(FCPATH . "uploads/category_images/" . $eachSubCategory['categoryImage'])) {
                    $img = base_url() . 'uploads/category_images/' . $eachSubCategory['categoryImage'];
                    $extraClass = '';
                }
                //$masterCategoryName = ($eachSubCategory['masterCategory']) ? $eachSubCategory['masterCategory'] : '-';
                $description = ($eachSubCategory['catDescription']) ? $eachSubCategory['catDescription'] : '-';
                $data[] = array($catId, '<div><img onerror=\'this.src="' . base_url('uploads/category_images/placeholder.png') . '"\' src="' . $img . '" width="100%" class="img-responsive img-thumbnail ' . $extraClass . '"></div>', $eachSubCategory['catName'], $description, '<a class="edit_sub_category action_icon" catId="' . $catId . '" title="Edit category"><i class="fa fa-edit"></i></a><a class="delete_category action_icon" catId="' . $catId . '"  title="Delete category"><i class="fa fa-remove text-danger"></i></a><a class="toggle_activation_category action_icon" catId="' . $catId . '" title="' . $display_title . '" toggle_Data = "' . $to_do . '"><i class="' . $icon_class . '"></i></a>', $eachSubCategory['isActive'], (string) $eachSubCategory['createdDateTime']);
            }
            echo json_encode(array('draw' => $draw, 'recordsTotal' => $totalSubCategory, 'recordsFiltered' => $totalSubCategory, 'data' => $data));
        } else {
            echo json_encode(array('draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => array()));
        }
    }

    /*
      Done by : 1081
      Description : This function to add or edit sub cateogry data
      Createa at : 12/08/2017
     */

    public function performSubCategory() {
        try {
            $catData = json_decode($this->input->raw_input_stream, true);
            if ($this->isLoggedIn()) {
                if ($catData['catId'] != '') {
                    $ImageString = $catData['profileImageString'];
                    unset($catData['profileImageString']);

                    if ($ImageString != "") {

                        $new_file_name = time() . ".jpg";

                        $catData['categoryImage'] = $new_file_name;

                        $file_upload_path = "./uploads/category_images/" . $new_file_name;

                        file_put_contents($file_upload_path, base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $ImageString)));
                    }
                    /* else{
                      $catData['categoryImage'] = "";
                      } */

                    $updateCat = $this->adminmodel->editSubCategory($catData);

                    if ($updateCat) {
                        echo json_encode(array("Result" => true, 'Message' => 'Category updated succesfully.'));
                    } else {
                        echo json_encode(array("Result" => false, 'Message' => 'Unable to save update sub category data'));
                    }
                } else {
                    // insert sub category
                    unset($catData['catId']);
                    //echo "<pre>";print_r($catData);
                    $ImageString = $catData['profileImageString'];
                    unset($catData['profileImageString']);

                    if ($ImageString != "") {

                        $new_file_name = time() . ".jpg";

                        $catData['categoryImage'] = $new_file_name;

                        $file_upload_path = "./uploads/category_images/" . $new_file_name;

                        file_put_contents($file_upload_path, base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $ImageString)));
                    } else {
                        $catData['categoryImage'] = "";
                    }
                    $insertNew = $this->adminmodel->addSubCategory($catData);
                    if ($insertNew) {
                        echo json_encode(array("Result" => true, 'Message' => 'New category inserted succesfully.'));
                    } else {
                        echo json_encode(array("Result" => false, 'Message' => 'Unable to save category data'));
                    }
                }
            }
        } catch (MongoDB\Driver\Exception\Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        } catch (Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        }
    }

    /*
      Done by : 1081
      Description : This function to get particular sub category data
      Createa at : 12/08/2017
     */

    public function getParticularSubCatData() {
        try {
            $catData = json_decode($this->input->raw_input_stream, true);
            if ($this->isLoggedIn()) {
                $foundCatData = $this->adminmodel->getSingleCatData($catData['catId']);
                if (!empty($foundCatData)) {
                    //$foundCatData['masterCategoryId'] = (string) $foundCatData['masterCategoryId'];
                    //unset($foundCatData['catId']);
                    echo json_encode(array("Result" => true, 'Message' => 'Category data found', 'Data' => $foundCatData));
                } else {
                    echo json_encode(array("Result" => false, 'Message' => 'Invalid category id.'));
                }
            }
        } catch (MongoDB\Driver\Exception\Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        } catch (Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        }
    }

    public function getParticularSubSubcatData() {
        try {
            $catData = json_decode($this->input->raw_input_stream, true);
            if ($this->isLoggedIn()) {
                $foundCatData = $this->adminmodel->getSingleMasterCatData($catData['catId']);


                if (!empty($foundCatData)) {
                    //$foundCatData['masterCategoryId'] = (string) $foundCatData['masterCategoryId'];
                    //unset($foundCatData['catId']);
                    $masterCategory = (string) $foundCatData[0]->masterCat;
                    $foundCatData = (array) $foundCatData[0];
                    $foundCatData['masterCat'] = $masterCategory;
                    echo json_encode(array("Result" => true, 'Message' => 'Category data found', 'Data' => $foundCatData));
                } else {
                    echo json_encode(array("Result" => false, 'Message' => 'Invalid category id.'));
                }
            }
        } catch (MongoDB\Driver\Exception\Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        } catch (Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        }
    }

    public function getParticularSalesPersonData() {
        try {
            $salesPersonData = json_decode($this->input->raw_input_stream, true);
            if ($this->isLoggedIn()) {
                $foundSalesPersonData = $this->adminmodel->getSingleSalesPersonData($salesPersonData['salesPersonId']);

                if (!empty($foundSalesPersonData)) {
                    $foundSalesPersonData = (array) $foundSalesPersonData[0];

                    $foundSalesPersonData['_id'] = (string) $foundSalesPersonData['_id'];
                    if (in_array($foundSalesPersonData['paymentMethod'], [2, 3])) {
                        $foundSalesPersonData['paymentDetail'] = (array) $foundSalesPersonData['paymentDetail'];
                        $foundSalesPersonData['paymentDetail']['state'] = (string) $foundSalesPersonData['paymentDetail']['state'];
                        $foundSalesPersonData['paymentDetail']['city'] = (string) $foundSalesPersonData['paymentDetail']['city'];
                    }

                    echo json_encode(array("Result" => true, 'Message' => 'Category data found', 'Data' => $foundSalesPersonData));
                } else {
                    echo json_encode(array("Result" => false, 'Message' => 'Invalid category id.'));
                }
            }
        } catch (MongoDB\Driver\Exception\Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        } catch (Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        }
    }

    function buyers() {
        if ($this->isLoggedIn()) {

            $header['active_page'] = "users";

            $this->load->view('admin/header', $header);
            $data = [];
            $this->load->view('admin/users_view', $data);
            $this->load->view('admin/users_js', array());
            $this->load->view('admin/footer');
        }
    }

    function vendors() {
        if ($this->isLoggedIn()) {

            $header['active_page'] = "vendors";

            $this->load->view('admin/header', $header);
            $states = $this->apimodel->get_states();
            $statesList = ['' => 'Select'];
            if (!empty($states)) {
                foreach ($states as $eachState) {
                    $statesList[$eachState['_id']] = $eachState['name'];
                }
            }


            //$companyData['statesList'] = $statesList;
            $data['states'] = $statesList;
            $this->load->view('admin/vendors_view', $data);
            $this->load->view('admin/vendors_js', array());
            $this->load->view('admin/footer');
        }
    }

    public function get_user_data($returnTransfer = false) {
        if ($returnTransfer) {
            $orderd[] = ['column' => 0, 'dir' => 'desc'];
            $dataTable = ['draw' => 0, 'start' => 0, 'length' => 10, 'order' => $orderd, 'search' => ['value' => '']];
        } else {
            $dataTable = $this->input->post(NULL, true);
        }
        $draw = $dataTable['draw'];
        $start = $dataTable['start'];
        $length = $dataTable['length'];

        $orderData = $dataTable['order'];
        $orderKeyIndex = $orderData[0]['column'];
        $direction = $orderData[0]['dir'];
        $search = $dataTable['search']['value'];

        $keys = array('createdDateTime', '_id', 'name', 'email', 'address', 'city', 'state', 'mobilePhone', 'facebookId', 'twitterId', 'action');

        $total = $this->adminmodel->getTotalUsers($search, 1);

        $directionInt = 1;
        if ($direction == 'desc') {
            $directionInt = -1;
        }

        if ($total > 0) {

            $users = $this->adminmodel->getUsers($start, $length, $search, $keys[$orderKeyIndex], $directionInt, 1, $returnTransfer);
            foreach ($users as $key => $eachuser) {
                $eachuser = (array) $eachuser;

                //echo "<pre>";print_r($eachuser);
                $_id = (string) $eachuser['_id'];

                if (!$returnTransfer) {
                    $image = "<div><img src='" . base_url() . 'uploads/user_profile/profile_placeholder.png' . "' width='100%' class='img-responsive img-thumbnail placeholder'/></div>";
                    if ($eachuser['profileImage'] != '' && file_exists(FCPATH . "uploads/user_profile/" . $eachuser['profileImage'])) {
                        $image = "<div><img  onerror=\"this.src='" . base_url('uploads/user_profile/profile_placeholder.png') . "'\"  src='" . base_url() . "uploads/user_profile/" . $eachuser['profileImage'] . "'  width='100%' class='img-responsive img-thumbnail' /></div>";
                    }
                } else {
                    $image = "-";
                    if ($eachuser['profileImage'] != '' && file_exists(FCPATH . "uploads/user_profile/" . $eachuser['profileImage'])) {
                        $image = base_url() . "uploads/user_profile/" . $eachuser['profileImage'];
                    }
                }

                $name = strlen(@$eachuser['name']) > 0 ? @$eachuser['name'] : '-';
                $email = strlen(@$eachuser['email']) > 0 ? @$eachuser['email'] : '-';
                $address = strlen(@$eachuser['address']) > 0 ? @$eachuser['address'] : '-';

                $city = @$eachuser['city'] != "" ? @$eachuser['city'] : '-';
                $state = @$eachuser['state'] != "" ? @$eachuser['state'] : '-';
                $phone = $eachuser['mobilePhone'] != "" ? $eachuser['mobilePhone'] : '-';
                $facebookId = strlen(@$eachuser['facebookId']) > 0 ? @$eachuser['facebookId'] : '-';
                $twitterId = strlen(@$eachuser['twitterId']) > 0 ? @$eachuser['twitterId'] : '-';

                if ($eachuser['isActive']) {
                    $icon_class = "fa fa-eye text-green";
                    $to_do = false;
                    $display_title = "Deactivate user";
                } else {
                    $icon_class = "fa fa-eye-slash text-danger";
                    $to_do = true;
                    $display_title = "Activate user";
                }




                if (isset($eachuser['createdDateTime'])) {
                    $registeredDatetime = $eachuser['createdDateTime']->toDateTime();
                    $registeredDatetime->setTimeZone(new DateTimeZone('America/Los_Angeles'));
                    $registeredDatetime = date('m/d/Y H:i:s', strtotime($registeredDatetime->format('Y-m-d H:i:s')));
                }

                $data[] = array($registeredDatetime, $image, $name, @$email, $address, $city, $state, $phone, $facebookId, $twitterId, "<a class='edit_user action_icon' userId='$_id' title='Edit user'><i class='fa fa-edit'></i></a>  <a class='action_icon' href='javascript:void(0)'><i class='fa fa-info-circle text-info' title='View user detail' style='cursor:pointer' data-title='view'  onClick=\"view_profile('" . $_id . "','isBuyer');\"></i></a><a class='delete_user action_icon' userId='" . $_id . "'  title='Delete user'><i class='fa fa-remove text-danger'></i></a><a class='toggle_activation_user action_icon' userId='" . $_id . "' title='" . $display_title . "' toggle_Data = '" . $to_do . "' userRole = " . $eachuser['userRole'] . "><i class='" . $icon_class . "'></i></a>");
            }

            if ($returnTransfer) {
                return $data;
            } else {
                echo json_encode(array('draw' => $draw, 'recordsTotal' => $total, 'recordsFiltered' => $total, 'data' => $data));
            }
        } else {
            echo json_encode(array('draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => array()));
        }
    }

    public function get_vendors_data($returnTransfer = false) {
        if ($returnTransfer) {
            $orderd[] = ['column' => 0, 'dir' => 'desc'];
            $dataTable = ['draw' => 0, 'start' => 0, 'length' => 10, 'order' => $orderd, 'search' => ['value' => '']];
        } else {
            $dataTable = $this->input->post(NULL, true);
        }

        $draw = $dataTable['draw'];
        $start = $dataTable['start'];
        $length = $dataTable['length'];

        $orderData = $dataTable['order'];
        $orderKeyIndex = $orderData[0]['column'];
        $direction = $orderData[0]['dir'];
        $search = $dataTable['search']['value'];

        $keys = array('createdDateTime', '_id', 'name', 'email', 'address', 'city', 'state', 'mobilePhone', 'description', 'action');

        $total = $this->adminmodel->getTotalUsers($search, 2);
        $directionInt = 1;

        if ($direction == 'desc') {
            $directionInt = -1;
        }

        if ($total > 0) {
            $users = $this->adminmodel->getUsers($start, $length, $search, $keys[$orderKeyIndex], $directionInt, 2, $returnTransfer);

//            $dd= $this->adminmodel->is_vendor_has_services();
            $data = array();
            $count = 1;
            foreach ($users as $key => $eachuser) {
                $eachuser = (array) $eachuser;

                $_id = (string) $eachuser['_id'];
                if (!$returnTransfer) {
                    $image = "<div><img src='" . base_url() . 'uploads/user_profile/profile_placeholder.png' . "' width='100%' class='img-responsive img-thumbnail placeholder'/></div>";

                    if (!empty($eachuser['companyProfileImage']) && file_exists(FCPATH . "uploads/company_profile/" . $eachuser['companyProfileImage'])) {
                        $image = "<div><img onerror=\"this.src='" . base_url('uploads/user_profile/profile_placeholder.png') . "'\" src='" . base_url() . "uploads/company_profile/" . $eachuser['companyProfileImage'] . "'  width='100%'  class='img-responsive img-thumbnail'/></div>";
                    }
                } else {
                    $image = "-";
                    if (!empty($eachuser['companyProfileImage']) && file_exists(FCPATH . "uploads/company_profile/" . $eachuser['companyProfileImage'])) {
                        $image = base_url() . "uploads/company_profile/" . $eachuser['companyProfileImage'];
                    }
                }
                $name = strlen(@$eachuser['name']) > 0 ? @$eachuser['name'] : '-';
                $description = strlen(@$eachuser['description']) > 0 ? @$eachuser['description'] : '-';
                $email = strlen(@$eachuser['email']) > 0 ? @$eachuser['email'] : '-';
                $address = strlen(@$eachuser['address']) > 0 ? @$eachuser['address'] : '-';
                $city = $eachuser['city'] != "" ? $eachuser['city'] : '-';
                $state = $eachuser['state'] != "" ? $eachuser['state'] : '-';
                $phone = $eachuser['mobilePhone'] != "" ? $eachuser['mobilePhone'] : '-';
                if ($eachuser['isActive']) {
                    $icon_class = "fa fa-eye text-green";
                    $to_do = false;
                    $display_title = "Deactivate vendor";
                } else {
                    $icon_class = "fa fa-eye-slash text-danger";
                    $to_do = true;
                    $display_title = "Activate vendor";
                }

                $base_url = base_url();



                if (isset($eachuser['createdDateTime'])) {
                    $registeredDatetime = $eachuser['createdDateTime']->toDateTime();
                    $registeredDatetime->setTimeZone(new DateTimeZone('America/Los_Angeles'));
                    $registeredDatetime = date('m/d/Y H:i:s', strtotime($registeredDatetime->format('Y-m-d H:i:s')));
                }

                $data[] = array("<input type='checkbox' id='deleteMulti_$count' name='deleteMulti[]' value='".$email."' count='".$total."' onchange='vendrcheck($count)' />", $registeredDatetime, $image, @$name, @$email, $address, $city, $state, $phone, @$description, "<a class='edit_vendor action_icon' vendorid='$_id' title='Edit vendor'><i class='fa fa-edit'></i></a> <a class='action_icon' href='javascript:void(0)'><i class='fa fa-info-circle text-info'  style='cursor:pointer' title='View vendor detail'  onClick=\"view_profile('" . $_id . "','isVendor');\"></i></a><a class='delete_user action_icon' userId='" . $_id . "'  title='Delete vendor'><i class='fa fa-remove text-danger'></i></a><a class='toggle_activation_user action_icon' userId='" . $_id . "' title='" . $display_title . "' toggle_Data = '" . $to_do . "' userRole = " . $eachuser['userRole'] . "><i class='" . $icon_class . "'></i></a><a class='' title='View vendor services' href='{$base_url}admin/vendor-services/?vendorid=$_id'><i class='fa fa-wrench'></i></a>");
            $count++; }

            if ($returnTransfer) {

                return $data;
            } else {
                echo json_encode(array('draw' => $draw, 'recordsTotal' => $total, 'recordsFiltered' => $total, 'data' => $data));
            }
        } else {
            echo json_encode(array('draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => array()));
        }
    }

    function GetAllVenEmail() {
        $totalemail = $this->adminmodel->FetchAllVendors();
        echo implode(",", $totalemail);
    }

    function SendBulkMails() {
        /*  ================ Normal mail service =================  */
        $this->load->library('emailsmsnotification');
        $dataTable = $this->input->post(NULL, true);
        
        $BulkEmails = explode(",", $dataTable['myCheckboxes']);
        $subject = $dataTable['subject'];
        $messages = $dataTable['msg'];

        $url = "https://us18.api.mailchimp.com/3.0/lists";
        $ch = curl_init($url);                                                                      
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST"); 
        curl_setopt($ch, CURLOPT_POSTFIELDS, '{"name":"Vendors1","contact":{"company":"DifferenzSystem","address1":"Adajan","address2":"","city":"Surat","state":"gujarat","zip":"395002","country":"india","phone":""},"permission_reminder":"Youre receiving this email because you signed up for updates about Freddies newest hats.","campaign_defaults":{"from_name":"Ketan","from_email":"ketanp@differenzsystem.com","subject":"","language":"en"},"email_type_option":true}');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Basic '.base64_encode("anystring:9f6fce7954eccc5eaa3aa4e4bb8bc2cb-us18")
        ));  
        $response2 = curl_exec($ch);
        $ezcash_array2 =json_decode($response2,true);
        $ListID = $ezcash_array2['id'];

        // $url = "https://us18.api.mailchimp.com/3.0/lists/".$ListID;
        // $ch = curl_init($url);                                                                      
        // curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST"); 
        // curl_setopt($ch, CURLOPT_POSTFIELDS, '{"members": [{"email_address": "safifarhan@gmail.com", "status": "subscribed"}]}');
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        // curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        //     'Content-Type: application/x-www-form-urlencoded',
        //     'Authorization: Basic '.base64_encode("anystring:5e6144bfe4de8bb1304282a1ef9e270b-us18")
        // ));  
        // $response2 = curl_exec($ch);
        // $ezcash_array2 =json_decode($response2,true);
        

        foreach ($BulkEmails as $emails) {
            $this->load->library('mailchimp_library');
            $result = $this->mailchimp_library->call('lists/subscribe', array(
                'id'                => $ListID,
                'email'             => array('email'=>$emails),
                'merge_vars'        => array('FNAME'=>'Davy', 'LNAME'=>'Jones'),
                'double_optin'      => false,
                'update_existing'   => true,
                'replace_interests' => false,
                'send_welcome'      => false,
            ));
        }
        
        echo $ListID.",".$subject.",".$messages;

       /*if ($flag) {
            echo json_encode(['Result' => true, 'Message' => 'Mail send successfully.']);
        } else {
            echo json_encode(['Result' => false, 'Message' => 'Mail could not be send!']);
        }*/
    }

    function AddCampaigns() {
       $ListID = $_POST['ListID'];
       $sub = $_POST['sub'];
       $msg = $_POST['msg'];
       $url = "https://us18.api.mailchimp.com/3.0/templates/2909";
        $ch = curl_init($url);                                                                      
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH"); 
        curl_setopt($ch, CURLOPT_POSTFIELDS, '{"name":"Test Template","html":"<!DOCTYPE html PUBLIC \"-\/\/W3C\/\/DTD XHTML 1.0 Transitional\/\/EN\" \"http:\/\/www.w3.org\/TR\/xhtml1\/DTD\/xhtml1-transitional.dtd\">\n<html>\n    <head>\n        <meta http-equiv=\"Content-Type\" content=\"text\/html; charset=UTF-8\">\n        \n        <meta property=\"og:title\" content=\"*|MC:SUBJECT|*\">\n        \n        <title>*|MC:SUBJECT|*<\/title>\n\t\t\n\t<style type=\"text\/css\">\n\t\t#outlook a{\n\t\t\tpadding:0;\n\t\t}\n\t\tbody{\n\t\t\twidth:100% !important;\n\t\t}\n\t\tbody{\n\t\t\t-webkit-text-size-adjust:none;\n\t\t}\n\t\tbody{\n\t\t\tmargin:0;\n\t\t\tpadding:0;\n\t\t}\n\t\timg{\n\t\t\tborder:none;\n\t\t\tfont-size:14px;\n\t\t\tfont-weight:bold;\n\t\t\theight:auto;\n\t\t\tline-height:100%;\n\t\t\toutline:none;\n\t\t\ttext-decoration:none;\n\t\t\ttext-transform:capitalize;\n\t\t}\n\t\t#backgroundTable{\n\t\t\theight:100% !important;\n\t\t\tmargin:0;\n\t\t\tpadding:0;\n\t\t\twidth:100% !important;\n\t\t}\n\t\tbody,.backgroundTable{\n\t\t\tbackground-color:#FAFAFA;\n\t\t}\n\t\t#templateContainer{\n\t\t\tborder:1px solid #DDDDDD;\n\t\t}\n\t\th1,.h1{\n\t\t\tcolor:#202020;\n\t\t\tdisplay:block;\n\t\t\tfont-family:Arial;\n\t\t\tfont-size:34px;\n\t\t\tfont-weight:bold;\n\t\t\tline-height:100%;\n\t\t\tmargin-bottom:10px;\n\t\t\ttext-align:left;\n\t\t}\n\t\th2,.h2{\n\t\t\tcolor:#202020;\n\t\t\tdisplay:block;\n\t\t\tfont-family:Arial;\n\t\t\tfont-size:30px;\n\t\t\tfont-weight:bold;\n\t\t\tline-height:100%;\n\t\t\tmargin-bottom:10px;\n\t\t\ttext-align:left;\n\t\t}\n\t\th3,.h3{\n\t\t\tcolor:#202020;\n\t\t\tdisplay:block;\n\t\t\tfont-family:Arial;\n\t\t\tfont-size:26px;\n\t\t\tfont-weight:bold;\n\t\t\tline-height:100%;\n\t\t\tmargin-bottom:10px;\n\t\t\ttext-align:left;\n\t\t}\n\t\th4,.h4{\n\t\t\tcolor:#202020;\n\t\t\tdisplay:block;\n\t\t\tfont-family:Arial;\n\t\t\tfont-size:22px;\n\t\t\tfont-weight:bold;\n\t\t\tline-height:100%;\n\t\t\tmargin-bottom:10px;\n\t\t\ttext-align:left;\n\t\t}\n\t\t#templatePreheader{\n\t\t\tbackground-color:#FAFAFA;\n\t\t}\n\t\t.preheaderContent div{\n\t\t\tcolor:#505050;\n\t\t\tfont-family:Arial;\n\t\t\tfont-size:10px;\n\t\t\tline-height:100%;\n\t\t\ttext-align:left;\n\t\t}\n\t\t.preheaderContent div a:link,.preheaderContent div a:visited{\n\t\t\tcolor:#336699;\n\t\t\tfont-weight:normal;\n\t\t\ttext-decoration:underline;\n\t\t}\n\t\t.preheaderContent div img{\n\t\t\theight:auto;\n\t\t\tmax-width:600px;\n\t\t}\n\t\t#templateHeader{\n\t\t\tbackground-color:#FFFFFF;\n\t\t\tborder-bottom:0;\n\t\t}\n\t\t.headerContent{\n\t\t\tcolor:#202020;\n\t\t\tfont-family:Arial;\n\t\t\tfont-size:34px;\n\t\t\tfont-weight:bold;\n\t\t\tline-height:100%;\n\t\t\tpadding:0;\n\t\t\ttext-align:center;\n\t\t\tvertical-align:middle;\n\t\t}\n\t\t.headerContent a:link,.headerContent a:visited{\n\t\t\tcolor:#336699;\n\t\t\tfont-weight:normal;\n\t\t\ttext-decoration:underline;\n\t\t}\n\t\t#headerImage{\n\t\t\theight:auto;\n\t\t\tmax-width:600px !important;\n\t\t}\n\t\t#templateContainer,.bodyContent{\n\t\t\tbackground-color:#FDFDFD;\n\t\t}\n\t\t.bodyContent div{\n\t\t\tcolor:#505050;\n\t\t\tfont-family:Arial;\n\t\t\tfont-size:14px;\n\t\t\tline-height:150%;\n\t\t\ttext-align:left;\n\t\t}\n\t\t.bodyContent div a:link,.bodyContent div a:visited{\n\t\t\tcolor:#336699;\n\t\t\tfont-weight:normal;\n\t\t\ttext-decoration:underline;\n\t\t}\n\t\t.bodyContent img{\n\t\t\tdisplay:inline;\n\t\t\tmargin-bottom:10px;\n\t\t}\n\t\t#templateFooter{\n\t\t\tbackground-color:#FDFDFD;\n\t\t\tborder-top:0;\n\t\t}\n\t\t.footerContent div{\n\t\t\tcolor:#707070;\n\t\t\tfont-family:Arial;\n\t\t\tfont-size:12px;\n\t\t\tline-height:125%;\n\t\t\ttext-align:left;\n\t\t}\n\t\t.footerContent div a:link,.footerContent div a:visited{\n\t\t\tcolor:#336699;\n\t\t\tfont-weight:normal;\n\t\t\ttext-decoration:underline;\n\t\t}\n\t\t.footerContent img{\n\t\t\tdisplay:inline;\n\t\t}\n\t\t#social{\n\t\t\tbackground-color:#FAFAFA;\n\t\t\tborder:1px solid #F5F5F5;\n\t\t}\n\t\t#social div{\n\t\t\ttext-align:center;\n\t\t}\n\t\t#utility{\n\t\t\tbackground-color:#FDFDFD;\n\t\t\tborder-top:1px solid #F5F5F5;\n\t\t}\n\t\t#utility div{\n\t\t\ttext-align:center;\n\t\t}\n\t\t#monkeyRewards img{\n\t\t\tmax-width:160px;\n\t\t}\n<\/style><\/head>\n    <body leftmargin=\"0\" marginwidth=\"0\" topmargin=\"0\" marginheight=\"0\" offset=\"0\">\n    \t<center>\n        \t<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" height=\"100%\" width=\"100%\" id=\"backgroundTable\">\n            \t<tr>\n                \t<td align=\"center\" valign=\"top\">\n                        <table border=\"0\" cellpadding=\"10\" cellspacing=\"0\" width=\"600\" id=\"templatePreheader\">\n                            <tr>\n                                <td valign=\"top\" class=\"preheaderContent\">\n                                \n                                    \n                                \n                                <\/td>\n                            <\/tr>\n                        <\/table>\n                    \t<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"600\" id=\"templateContainer\">\n                        \t<tr>\n                            \t<td align=\"center\" valign=\"top\">\n                                \t<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"600\" id=\"templateHeader\">\n                                        <tr>\n                                            <td class=\"headerContent\">\n                                            \n                                            \t <br\/><img src=\"https:\/\/gallery.mailchimp.com\/32ec1eb0d7650b5d010ea3963\/images\/58b38a99-0c77-41a9-8c41-660009713435.png\" style=\"max-width:600px;\" id=\"headerImage campaign-icon\" mc:label=\"header_image\" mc:edit=\"header_image\" mc:allowdesigner=\"\" mc:allowtext=\"\">\n                                            \n                                            <\/td>\n                                        <\/tr>\n                                    <\/table>\n                                <\/td>\n                            <\/tr>\n                        \t<tr>\n                            \t<td align=\"center\" valign=\"top\">\n                                \t<table border=\"0\" cellpadding=\"10\" cellspacing=\"0\" width=\"600\" id=\"templateBody\">\n                                    \t<tr>\n                                            <td valign=\"top\" class=\"bodyContent\">\n                                \n                                                <table border=\"0\" cellpadding=\"10\" cellspacing=\"0\" width=\"100%\">\n                                                    <tr>\n                                                        <td valign=\"top\">\n                                                            <div mc:edit=\"std_content00\">\n                                                                '.$msg.'.\n                                                                                                                            <\/div>\n\t\t\t\t\t\t\t\t\t\t\t\t\t\t<\/td>\n                                                    <\/tr>\n                                                <\/table>                                              \n                                            <\/td>\n                                        <\/tr>\n                                    <\/table>\n                                <\/td>\n                            <\/tr>\n                        \t<tr>\n                            \t<td align=\"center\" valign=\"top\">\n                                \t<table border=\"0\" cellpadding=\"10\" cellspacing=\"0\" width=\"600\" id=\"templateFooter\">\n                                    \t<tr>\n                                        \t<td valign=\"top\" class=\"footerContent\">\n                                                <table border=\"0\" cellpadding=\"10\" cellspacing=\"0\" width=\"100%\">\n                                                    <tr>\n                                                        <td colspan=\"2\" valign=\"middle\" id=\"social\">\n                                                            <div mc:edit=\"std_social\">\n                                                                &nbsp;<a href=\"*|TWITTER:PROFILEURL|*\">follow on Twitter<\/a> | <a href=\"*|FACEBOOK:PROFILEURL|*\">friend on Facebook<\/a> | <a href=\"*|FORWARD|*\">forward to a friend<\/a>&nbsp;\n                                                            <\/div>\n                                                        <\/td>\n                                                    <\/tr>\n                                                    <tr>\n                                                        <td valign=\"top\" width=\"370\">\n                                                            <br>\n                                                            <div mc:edit=\"std_footer\">\n                                                                *|IF:LIST|*\n                                                                <em>Copyright &copy; *|CURRENT_YEAR|* *|LIST:COMPANY|*, All rights reserved.<\/em>\n                                                                <br>\n                                                                <!-- *|IFNOT:ARCHIVE_PAGE|* -->\n                                                                *|LIST:DESCRIPTION|*\n                                                                <br>\n                                                                <strong>Our mailing address is:<\/strong>\n                                                                <br>\n                                                                *|HTML:LIST_ADDRESS_HTML|*\n                                                                <br>\n                                                                <!-- *|END:IF|* -->\n                                                                *|ELSE:|*\n                                                                <!-- *|IFNOT:ARCHIVE_PAGE|* -->\n                                                                <em>Copyright &copy; *|CURRENT_YEAR|* *|USER:COMPANY|*, All rights reserved.<\/em>\n                                                                <br>\n                                                                <strong>Our mailing address is:<\/strong>\n                                                                <br>\n                                                                *|USER:ADDRESS_HTML|*\n                                                                <!-- *|END:IF|* -->\n                                                                *|END:IF|*\n                                                            <\/div>\n                                                            <br>\n                                                        <\/td>\n                                                                                                          <\/tr>\n                                                                                                    <\/table>\n                                            \n                                            <\/td>\n                                        <\/tr>\n                                    <\/table>\n                                <\/td>\n                            <\/tr>\n                        <\/table>\n                        <br>\n                    <\/td>\n                <\/tr>\n            <\/table>\n        <\/center>\n    <\/body>\n<\/html>"}');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Basic '.base64_encode("anystring:9f6fce7954eccc5eaa3aa4e4bb8bc2cb-us18")
        ));  
        $response9 = curl_exec($ch);
        $ezcash_array9 =json_decode($response9,true);

       $url = "https://us18.api.mailchimp.com/3.0/campaigns";
        $ch = curl_init($url);                                                                      
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST"); 
        curl_setopt($ch, CURLOPT_POSTFIELDS, '{"recipients":{"list_id":"'.$ListID.'"},"type":"regular","settings":{"subject_line":"'.$sub.'","reply_to":"ketanp@differenzsystem.com","from_name":"EZList","title":"VendorCampaign","template_id":2909}}');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Basic '.base64_encode("anystring:9f6fce7954eccc5eaa3aa4e4bb8bc2cb-us18")
        ));  
        $response3 = curl_exec($ch);
        
        echo "<pre>";
        print_r($response3);
        $ezcash_array3 =json_decode($response3,true);
        $_SESSION['campaign_ID'] = $ezcash_array3['id'];
        echo $ezcash_array3['id'];
    }

    function SendCampaigns() {
        /*$CampaignID = $_POST['CampaignID'];*/
        $CampaignID = $_SESSION['campaign_ID'];
        $url = "https://us18.api.mailchimp.com/3.0/campaigns/".$CampaignID."/actions/send";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Basic '.base64_encode("anystring:9f6fce7954eccc5eaa3aa4e4bb8bc2cb-us18")
        ));
        $response4 = curl_exec($ch);
        $ezcash_array4 =json_decode($response4,true);
        unset($_SESSION['campaign_ID']);
    }

    function vendor_services() {


        if ($this->isLoggedIn()) {
            $input = $this->input->get();
            $vendorid = $input['vendorid'];

            $header['active_page'] = "vendors";

            $this->load->view('admin/header', $header);

            $data = ['vendorid' => $vendorid];
            $this->load->view('admin/vendors_services_view', $data);
            $this->load->view('admin/vendors_js', array());
            $this->load->view('admin/footer');
        }
    }

    function get_vendors_services_data() {
        $input = $this->input->get();
        $dataTable = $this->input->post(NULL, true);

        $draw = $dataTable['draw'];
        $start = $dataTable['start'];
        $length = $dataTable['length'];

        $orderData = $dataTable['order'];
        $orderKeyIndex = $orderData[0]['column'];
        $direction = $orderData[0]['dir'];
        $search = $dataTable['search']['value'];

        $keys = array('_id', 'screenName', 'serviceDetail', 'flatFee', 'action', 'createdDateTime');

        $vendorid = $input['vendorid'];

        $total = $this->adminmodel->getTotalVendorServices($vendorid, $search);

        $directionInt = 1;
        if ($direction == 'desc') {
            $directionInt = -1;
        }

        if ($total > 0) {

            $services = $this->adminmodel->getVendorServices($vendorid, $start, $length, $search, $keys[$orderKeyIndex], $directionInt);


            $data = array();
            foreach ($services as $key => $eachservice) {

                $eachservice = (array) $eachservice;

                $this->mongo_db->where(array('serviceId' => $eachservice['_id']));
                $this->mongo_db->where_in('status', array(0, 1, 2, 3, 4));
                //$this->mongo_db->where_ne('status',6);
                //$this->mongo_db->where_ne('status',5);
                $requestCount = $this->mongo_db->count('service_requests');
                if ($requestCount > 0) {
                    $eachService['editAble'] = false;
                } else {
                    $eachService['editAble'] = true;
                }



                $_id = (string) $eachservice['_id'];

                $image = "<div><img src='" . base_url() . 'uploads/category_images/placeholder.png' . "' width='100%' class='img-responsive img-thumbnail placeholder'/></div>";
                if (!empty($eachservice['serviceImage']) && file_exists(FCPATH . "uploads/service_images/" . $eachservice['serviceImage'])) {

                    $image = "<div><img onerror=\"this.src='" . base_url('uploads/category_images/placeholder.png') . "'\" src='" . base_url() . "uploads/service_images/" . $eachservice['serviceImage'] . "'  width='100px'  /></div>";
                }

                $name = strlen(@$eachservice['screenName']) > 0 ? @$eachservice['screenName'] : '-';
                $serviceDetail = strlen(@$eachservice['serviceDetail']) > 0 ? @$eachservice['serviceDetail'] : '-';

                $flatFee = @$eachservice['flatFee'];
                $serviceEditable = (int) $eachService['editAble'];



                if (!$eachservice['isAvailable']) {
                    $icon_class = "fa fa-eye text-green";
                    $to_do = false;
                    $display_title = "Available by Appointment";
                } else {
                    $icon_class = "fa fa-eye-slash text-danger";
                    $to_do = true;
                    $display_title = "Available Now";
                }

                $deleteAction = "";
                /* if($eachService['editAble']){
                  $deleteAction = "<a class='delete_user action_icon' userId='" . $_id . "'  title='Delete service'><i class='fa fa-remove text-danger'></i></a>";
                  } */

                $base_url = base_url();

                $data[] = array($image, @$name, $serviceDetail, @$flatFee, "<form method='post' action='{$base_url}admin/edit-service'><input type='hidden' name='serviceId' value='{$_id}' /><input type='hidden' name='userId' value='{$vendorid}' /><input type='hidden' name='serviceEditable' value='{$serviceEditable}' /></form><a class='edit_service action_icon' serviceid='$_id' href='' title='Edit service'><i class='fa fa-edit'></i></a> {$deleteAction}<a class='toggle_available_service action_icon' serviceid='$_id' title='{$display_title}' toggle_Data = '" . $to_do . "'><i class='" . $icon_class . "'></i></a>", (string) @$eachuser['createdDateTime']);
            }
            echo json_encode(array('draw' => $draw, 'recordsTotal' => $total, 'recordsFiltered' => $total, 'data' => $data));
        } else {
            echo json_encode(array('draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => array()));
        }
    }

    public function add_service() {

        $admindata = $this->session->userdata('admindata');
        $this->session->set_userdata('userdata', $admindata);
        $postData = $this->input->post();
        $this->load->helper('form');

        $categories = $this->apimodel->get_categories();
        //echo "<pre>";print($categories);
        $categoryData = array('' => 'Select');
        foreach ($categories as $category) {
            $categoryData[$category['_id']] = $category['catName'];
        }


        $oldData['month'] = (int) date('n') - 1; //App guys are storing zero-based index
        $oldData['year'] = (int) date('Y');
        $oldData['userId'] = $postData['vendorid'];

        /* Get allocated dates of current month - year. */
        $allocatedDates = $this->apimodel->getAllocatedDates1($oldData['month'], $oldData['year'], $oldData['userId']);
        $allocatedDays = $this->apimodel->getAllocatedDays1($oldData['month'], $oldData['year'], $oldData['userId']);
        //echo "<pre>";print_r($allocatedDays);

        $vendor_data = array();
        /* if($oldData['duration'] != "" && $oldData['day'] != ""){
          //echo "in if";
          $vendor_data = $this->apimodel->getAllocatedTimeslot($vendor_data);
          } */


        $time_slot = $this->apimodel->getAllTimeSlot();

        $days = array(0 => 'Monday', 1 => 'Tuesday', 2 => 'Wednesday', 3 => 'Thursday', 4 => 'Friday', 5 => 'Saturday', 6 => 'Sunday');
        $hours = array('' => 'Select', 1 => '1 Hour', 2 => '2 Hours', 3 => '3 Hours', 4 => '4 Hours');
        $availableDays = array();
        foreach ($days as $key => $value) {
            $eachday['day'] = $key;
            $eachday['isAllocate'] = false;
            /* if (in_array($key, $allocatedDays)) {
              $eachday['isAllocate'] = true;
              } else {
              $eachday['isAllocate'] = false;
              } */
            array_push($availableDays, $eachday);
        }

        $timeSlot = array();
        foreach ($time_slot as $key => $value) {

            $timeSlot[$key]['duration'] = $value['duration'];
            $timeSlot[$key]['timeslot'] = array();
            foreach ($value['timeslot'] as $timekey => $eachtime) {
                $temp_time['time'] = $eachtime;
                $temp_time['allocated'] = false;
                array_push($timeSlot[$key]['timeslot'], $temp_time);
            }
        }

        $addServiceData = array();

        $addServiceData['categories'] = $categoryData;
        $addServiceData['days'] = $days;
        $addServiceData['hours'] = $hours;
        $addServiceData['timeSlot'] = $timeSlot;
        $addServiceData['availableDays'] = $availableDays;
        $addServiceData['userId'] = $postData['vendorid'];
        $addServiceData['serviceEditAble'] = true;

        $data['content'] = $this->load->view('vendor/add-service', $addServiceData, true);

        $header['active_page'] = "vendors";

        $this->load->view('admin/header', $header);

        $addServiceData['returnUrl'] = base_url() . "admin/vendor-services/?vendorid=" . $postData['vendorid'];

        $this->load->view('admin/add-service', $addServiceData);
        $this->load->view('admin/add-service-js', $addServiceData);
        $this->load->view('admin/footer');
    }

    public function edit_service() {
        header("Cache-Control: no cache");
        session_cache_limiter("private_no_expire");
        $admindata = $this->session->userdata('admindata');
        $this->session->set_userdata('userdata', $admindata);
        $postData = $this->input->post(NULL, true);
        if (!empty($postData)) {

            $sessdata = $this->session->userdata('userdata');


            $serviceId = $postData['serviceId'];
            $serviceEditAble = true;
            if ($postData['serviceEditable'] == 0) {
                $serviceEditAble = false;
            }

            $month = (int) date('n') - 1; //App guys are storing zero-based index
            $year = (int) date('Y');
            $jsondata = array("fromDesktop" => "true", "userId" => $postData['userId'], "serviceId" => $serviceId, "month" => $month, "year" => $year);
            $data_string = json_encode($jsondata);
            //echo "url".base_url() . 'api/getDataEditService';
            //echo "<br>data string".$data_string;
            $ch = curl_init(base_url() . 'api/getDataEditService');
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string))
            );
            $result = curl_exec($ch);
            $response = json_decode($result, true);
            $header['active_page'] = "vendors";

            $this->load->view('admin/header', $header);
            if ($response['Result']) {
                $this->load->helper('form');
                $categories = $this->apimodel->get_categories();
                $categoryData = array('' => 'Select');
                foreach ($categories as $category) {
                    $categoryData[$category['_id']] = $category['catName'];
                }
                $hours = array('' => 'Select', 1 => '1 Hour', 2 => '2 Hours', 3 => '3 Hours', 4 => '4 Hours');
                $days = array(0 => 'Monday', 1 => 'Tuesday', 2 => 'Wednesday', 3 => 'Thursday', 4 => 'Friday', 5 => 'Saturday', 6 => 'Sunday');
                $editServiceData = $response['Data'];
                $editServiceData['timeSlot'] = $editServiceData['timeslot'];
                $editServiceData['categories'] = $categoryData;
                $editServiceData['hours'] = $hours;
                $editServiceData['days'] = $days;
                $editServiceData['serviceId'] = $serviceId;
                $editServiceData['serviceEditAble'] = $serviceEditAble;
                $editServiceData['userId'] = $postData['userId'];
                $editServiceData['returnUrl'] = base_url() . "admin/vendor-services/?vendorid=" . $postData['userId'];
                $this->load->view('admin/edit-service', $editServiceData);
            }



            //Making this for jsong object
            $duration = "";
            if (!empty($editServiceData['allocate'])) {
                $duration = $editServiceData['allocate'][0]['duration'];
            }
            $editServiceData['availableSchedule'] = array("month" => $month, "year" => $year, "duration" => $duration, "day" => $editServiceData['serviceDetail']['availableDay'], "timings" => $editServiceData['serviceDetail']['availableTimes']);


            $editServiceData['returnUrl'] = base_url() . "admin/vendor-services/?vendorid=" . $postData['userId'];
            $this->load->view('admin/add-service-js', $editServiceData);
            $this->load->view('admin/footer');
        } else {
            redirect(base_url() . 'vendor/my-services');
        }
    }

    function getUserById() {
        $postData = array_map('trim', $this->input->post(NULL, true));

        if (!empty($postData)) {
            $result = $this->adminmodel->getUserById($postData);
            $result = (array) $result;
            //$result['password'] = password_hash($result['password'], PASSWORD_DEFAULT);
            if ($postData['userType'] == 'isVendor') {
                $bussinessCreated = $this->apimodel->is_business_created($postData['_id']);
                if ($bussinessCreated) {
                    $companyProfile = $this->apimodel->get_company_profile($postData['_id']);
                    $paymentDetail = array();
                    if (in_array($companyProfile['paymentMethod'], [2, 3])) {
                        $paymentDetail = (array) $this->apimodel->get_payment_mode_detail(new MongoDB\BSON\ObjectID($postData['_id']));
                        if (!empty($paymentDetail)) {
                            $paymentDetail = (array) $paymentDetail['paymentDetail'];
                            $paymentDetail['city'] = (string) $paymentDetail['city'];
                            $paymentDetail['state'] = (string) $paymentDetail['state'];
                        }
                    }
                    $companyProfile['paymentDetail'] = $paymentDetail;
                    $getReviews = $this->apimodel->get_company_review($companyProfile['companyId']);
                    $companyProfile['serviceReviews'] = $getReviews;
                    $overAllRating = $this->apimodel->getOverviewRating($companyProfile['companyId']);
                    $companyProfile['overAllRating'] = $overAllRating;
                    $result['companyProfile'] = $companyProfile;
                }
                $result['bussinessCreated'] = $bussinessCreated;
            }
            if ($postData['userType'] == 'isBuyer') {
                if (!isset($result['firstName']) && !isset($result['lastName'])) {
                    $name_arr = explode(" ", $result['name']);
                    $result['firstName'] = $name_arr[0];
                    $result['lastName'] = $name_arr[1];
                    //echo "<pre>";print_r($name_arr);
                }
            }
            echo json_encode(array('Result' => true, 'data' => $result));
        } else {
            $this->Message("Invalid request");
        }
    }

    /*
      Done by : 1081
      Description : This function to get shopby uber picks view
      Createa at : 12/08/2017
     */

    public function TheEZListPicks() {
        if ($this->isLoggedIn()) {

            $header['active_page'] = "TheEZListpicks";

            $this->load->view('admin/header', $header);

            $this->load->view('admin/shopbyuberpicks', array());
            $this->load->view('admin/shopbyuberpicks_js', array());
            $this->load->view('admin/footer');
        }
    }

    /*
      Done by : 1081
      Description : This function to get company data using ajax
      Createa at : 12/08/2017
     */

    public function getCompanydata() {
        try {
            $dataTable = $this->input->post(NULL, true);
            //echo "<pre>";print_r($dataTable);
            $draw = $dataTable['draw'];
            $start = $dataTable['start'];
            $length = $dataTable['length'];

            $orderData = $dataTable['order'];
            $orderKeyIndex = $orderData[0]['column'];
            $direction = $orderData[0]['dir'];
            $search = $dataTable['search']['value'];

            $directionInt = 1;
            if ($direction == 'desc') {
                $directionInt = -1;
            }

            $keys = array('_id', 'image', 'vendorName', 'companyName', 'comapnyScreenName', 'companyAddress', 'serviceDescription', 'createdAt');

            $totalCompany = $this->adminmodel->getTotalCompany($search);

            if ($totalCompany > 0) {
                $data = array();
                $companies = $this->adminmodel->getCompanies($start, $length, $directionInt, $keys[$orderKeyIndex], $search);
                foreach ($companies as $key => $eachCompany) {
                    $eachCompany = (array) $eachCompany;
                    //echo "<pre>";print_r($eachSubCategory);

                    $companyId = (string) $eachCompany['_id'];
                    $vendorId = (string) $eachCompany['vendorId'];
                    if ($eachCompany['isRecommended']) {
                        $icon_class = "fa fa-thumbs-up text-green";
                        $to_do = false;
                        $display_title = "Remove from recommended";
                    } else {
                        $icon_class = "fa fa-thumbs-down text-danger";
                        $to_do = true;
                        $display_title = "Add to recommended";
                    }
                    $img = base_url() . 'uploads/company_profile/placeholder.png';
                    if ($eachCompany['companyProfileImage'] != '') {
                        $img = base_url() . 'uploads/company_profile/' . $eachCompany['companyProfileImage'];
                    }
                    if ($eachCompany['serviceDescription'] != '') {
                        $descriptionLength = strlen($eachCompany['serviceDescription']);
                        if ($descriptionLength > 55) {
                            $eachCompany['serviceDescription'] = substr($eachCompany['serviceDescription'], 0, 55) . '..';
                        }
                    } else {
                        $eachCompany['serviceDescription'] = '-';
                    }

                    $companyName = ($eachCompany['companyName']) ? $eachCompany['companyName'] : '-';
                    $comapnyScreenName = ($eachCompany['comapnyScreenName']) ? $eachCompany['comapnyScreenName'] : '-';
                    $companyAddress = ($eachCompany['companyAddress']) ? $eachCompany['companyAddress'] : '-';

                    $data[] = array($companyId, '<div><img src="' . $img . '" width="100%" class="img-responsive img-thumbnail"></div>', $eachCompany['vendorName'], $companyName, $comapnyScreenName, $companyAddress, $eachCompany['serviceDescription'], (string) $eachCompany['createdAt'], '<a class="toggle_activation_company action_icon" title="' . $display_title . '" toggle_Data = "' . $to_do . '"><i class="' . $icon_class . '"></i></a><a class="view_company_profile action_icon" title="View company profile"><i class="fa fa-info-circle text-info"></i></a>', $eachCompany['isRecommended'], $vendorId);
                }
                echo json_encode(array('draw' => $draw, 'recordsTotal' => $totalCompany, 'recordsFiltered' => $totalCompany, 'data' => $data));
            } else {
                echo json_encode(array('draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => array()));
            }
        } catch (MongoDB\Driver\Exception\Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        } catch (Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        }
    }

    /*
      Done by : 1081
      Description : This function to active/deactive master category
      Createa at : 14/08/2017
     */

    public function recommendedCompany() {
        try {
            $masterCatData = json_decode($this->input->raw_input_stream, true);
            if ($this->isLoggedIn()) {
                $deleteFlag = $this->adminmodel->recommendedCompany($masterCatData['companyId'], $masterCatData['isRecommended']);
                if ($deleteFlag) {
                    echo json_encode(array("Result" => true, 'Message' => 'Succesfully toggle this company'));
                } else {
                    echo json_encode(array("Result" => false, 'Message' => 'Unable to toggle this company'));
                }
            }
        } catch (MongoDB\Driver\Exception\Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        } catch (Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        }
    }

    /*
      Done by : 1081
      Description : This function to get specific company Data
      Createa at : 14/08/2017
     */

    public function getCompanyProfile() {
        try {
            $companyData = json_decode($this->input->raw_input_stream, true);
            if ($this->isLoggedIn()) {
                $companyProfile = $this->apimodel->get_company_profile($companyData['vendorId']);
                //
                if (!empty($companyProfile)) {
                    $getReviews = $this->apimodel->get_company_review($companyData['companyId']);
                    $companyProfile['serviceReviews'] = $getReviews;
                    $overAllRating = $this->apimodel->getOverviewRating($companyData['companyId']);
                    $companyProfile['overAllRating'] = $overAllRating;

                    echo json_encode(array('Result' => true, 'Message' => 'Company profile is listing below', 'Data' => $companyProfile));
                } else {
                    echo json_encode(array('Result' => false, 'Message' => 'Sorry!! unable to get company profile'));
                }
                //$companyProfile = $this->apimodel->
            }
        } catch (MongoDB\Driver\Exception\Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        } catch (Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        }
    }

    public function performVendor() {
        try {
            $data = json_decode($this->input->raw_input_stream, true);

            if ($this->isLoggedIn()) {
                //Check promo code
                if ($data['promoCode'] != "" && $data['promoCode'] != NULL) {

                    $promoCodeExists = $this->apimodel->checkPromoCodeExists($data['promoCode']);

                    if (!empty($promoCodeExists)) {
                        $promoCodeExists = (array) $promoCodeExists[0];

                        if (!$promoCodeExists['isActive']) {
                            header('Content-type:application/json');
                            echo json_encode(array("Result" => false, 'Message' => 'Sorry!! This Promo Code is no longer active!'));
//                            die;
                        }
                    } else {
                        header('Content-type:application/json');
                        echo json_encode(array("Result" => false, 'Message' => 'Sorry!! This Promo Code is not valid!'));
//                        die;
                    }
                }

                if ($data['vendorId'] != '') {
                    $vendordata['phoneNumber'] = $data['phoneNumber'];
                    $vendordata['countryCode'] = $data['countryCode'];
                    $vendordata['mobilePhone'] = $data['countryCode'] . $data['phoneNumber'];
                    $vendordata['legalBusinessName'] = $data['legalBusinessName'];
                    $vendordata['description'] = '';
                    $vendordata['speciality'] = '';
                    $vendordata['email'] = $data['email'];
                    $vendordata['userId'] = $data['vendorId'];
                    $vendordata['timeZone'] = $data['timeZone'];
                    $vendordata['name'] = $data['name'];
                    $vendordata['firstName'] = $data['firstName'];
                    $vendordata['lastName'] = $data['lastName'];
                    $vendordata['address'] = $data['companyAddress'];
                    $vendordata['latitude'] = (float) $data['latitude'];
                    $vendordata['longitude'] = (float) $data['longitude'];
                    $vendordata['city'] = $data['city'];
                    $vendordata['state'] = $data['state'];
                    $vendordata['country'] = $data['country'];

                    $profileImageString = $data['profileImageString'];
                    unset($data['profileImageString']);

                    if ($profileImageString != "") {

                        $new_file_name = uniqid() . ".jpg";

                        $vendordata['profileImage'] = $new_file_name;

                        //$file_upload_path = "./uploads/user_profile/" . $new_file_name;
                        $compony_file_upload_path = "./uploads/company_profile/" . $new_file_name;

                        //file_put_contents($file_upload_path, base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $profileImageString)));

                        file_put_contents($compony_file_upload_path, base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $profileImageString)));
                    }
                    $update = $this->adminmodel->editUser($vendordata);


                    if ($update) {
                        $companyProfile['vendorId'] = new MongoDB\BSON\ObjectID($data['vendorId']);
                        $companyProfile['companyName'] = '';
                        $companyProfile['ownerName'] = $data['name'];
                        $companyProfile['firstName'] = $data['firstName'];
                        $companyProfile['lastName'] = $data['lastName'];
                        $companyProfile['comapnyScreenName'] = $data['companyScreenName'];
                        $companyProfile['companyAddress'] = $data['companyAddress'];
                        $companyProfile['latitude'] = (float) $data['latitude'];
                        $companyProfile['longitude'] = (float) $data['longitude'];
                        $companyProfile['zipcode'] = $data['zipcode'];
                        $companyProfile['countryCode'] = $data['countryCode'];
                        $companyProfile['companyPhoneNumber'] = $data['companyPhoneNumber'];
                        $companyProfile['companyPhone'] = $data['countryCode'] . $data['companyPhoneNumber'];
                        $companyProfile['websiteUrl'] = '';
                        $companyProfile['paymentMethod'] = $data['paymentMethod'];
                        $companyProfile['paypalEmail'] = $data['paypalEmail'];
                        $companyProfile['serviceDescription'] = $data['serviceDescription'];
                        $companyProfile['isActive'] = true;
                        $companyProfile['promoCode'] = $data['promoCode'];

                        
                        if ($profileImageString != "") {
                            $companyProfile['companyProfileImage'] = $new_file_name;
                        }
                        $bussinessCreated = $this->apimodel->is_business_created($data['vendorId']);
                        /* echo "<pre>";print_r($companyProfile);
                          echo "business created".$bussinessCreated;
                         */
                        if ($bussinessCreated) {
//                            echo "<pre>";
//                            print_r($companyProfile);
//                            die;
                            $update = $this->adminmodel->editCompanyProfile($companyProfile);
                        } else {
//                            echo "<pre>";
//                            print_r($companyProfile);
//                            die;
                            $newCompany = $this->adminmodel->addCompanyProfile($companyProfile);

                            $update = false;
                            if (!empty($newCompany)) {
                                $update = true;
                            }
                        }
                        if (in_array($data['paymentMethod'], [2, 3])) {
                            //Make entry in payment_mode_details table
                            $paymentDetail = $data['paymentDetail'];
                            $this->apimodel->update_payment_mode($data['vendorId'], $paymentDetail);
                        } else {
                            //Remove existing record if any before payment method change
                            $this->apimodel->remove_payment_mode($data['vendorId']);
                        }
                    }
                    if ($update) {
                        echo json_encode(array("Result" => true, 'Message' => 'Vendor updated succesfully.'));
                    } else {
                        echo json_encode(array("Result" => false, 'Message' => 'Unable to update vendor data'));
                    }
                } else {
                    $check_exists = $this->apimodel->check_email_exists($data['email']);
                    if (!$check_exists) {
                        $vendordata = [];
                        $vendordata['userRole'] = 2;
                        $vendordata['firstName'] = $data['firstName'];
                        $vendordata['lastName'] = $data['lastName'];
                        $vendordata['name'] = $data['name'];
                        $vendordata['mobilePhone'] = $data['countryCode'] . $data['phoneNumber'];
                        $vendordata['countryCode'] = $data['countryCode'];
                        $vendordata['phoneNumber'] = $data['phoneNumber'];
                        $vendordata['legalBusinessName'] = $data['legalBusinessName'];
                        $vendordata['password'] = $data['password'];
                        $vendordata['profileImage'] = $data['vendorImageName'];
                        $vendordata['email'] = $data['email'];

                        $vendordata['description'] = "";
                        $vendordata['speciality'] = "";
                        $vendordata['timeZone'] = $data['timeZone'];
                        $vendordata['isActive'] = true;
                        $vendordata['isBuyer'] = false;
                        $vendordata['isVendor'] = true;
                        $vendordata['registerFrom'] = 'admin';
                        $vendordata['quickBloxId'] = (int) 0;

                        $vendordata['address'] = $data['companyAddress'];
                        $vendordata['latitude'] = (float) $data['latitude'];
                        $vendordata['longitude'] = (float) $data['longitude'];
                        $vendordata['city'] = $data['city'];
                        $vendordata['state'] = $data['state'];
                        $vendordata['country'] = $data['country'];
                        $vendordata['ezListScreenName'] = $data['companyScreenName'];

                        $vendordata['basicNotification'] = $this->basic_notification;
                        $vendordata['otherNotification'] = $this->vendor_notification;

                        $profileImageString = $data['profileImageString'];

                        unset($data['profileImageString']);

                        $new_file_name = uniqid() . ".jpg";

                        //$vendordata['profileImage'] = $new_file_name;

                        //$file_upload_path = "./uploads/user_profile/" . $new_file_name;
                        $compony_file_upload_path = "./uploads/company_profile/" . $new_file_name;

                        //file_put_contents($file_upload_path, base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $profileImageString)));

                        file_put_contents($compony_file_upload_path, base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $profileImageString)));

                        $insertNew = $this->adminmodel->addUser($vendordata);

                        if ($insertNew) {
                            $companyProfile = [];
                            $companyProfile['vendorId'] = new MongoDB\BSON\ObjectID($insertNew);
                            $companyProfile['companyName'] = '';
                            $companyProfile['ownerName'] = $data['name'];
                            $companyProfile['firstName'] = $data['firstName'];
                            $companyProfile['lastName'] = $data['lastName'];
                            $companyProfile['comapnyScreenName'] = $data['companyScreenName'];
                            $companyProfile['companyAddress'] = $data['companyAddress'];
                            $companyProfile['latitude'] = (float) $data['latitude'];
                            $companyProfile['longitude'] = (float) $data['longitude'];
                            $companyProfile['zipcode'] = $data['zipcode'];
                            $companyProfile['websiteUrl'] = '';
                            $companyProfile['paypalEmail'] = "";
                            $companyProfile['serviceDescription'] = $data['serviceDescription'];
                            $companyProfile['countryCode'] = $data['countryCode'];
                            $companyProfile['companyPhoneNumber'] = $data['companyPhoneNumber'];
                            $companyProfile['companyPhone'] = $data['countryCode'] . $data['companyPhoneNumber'];
                            $companyProfile['companyProfileImage'] = $new_file_name;
                            $companyProfile['paymentMethod'] = $data['paymentMethod'];
                            $companyProfile['paypalEmail'] = $data['paypalEmail'];
                            $companyProfile['isActive'] = true;
                            $companyProfile['promoCode'] = $data['promoCode'];



                            $newCompany = $this->adminmodel->addCompanyProfile($companyProfile);

                            if ($newCompany) {
                                if (in_array($data['paymentMethod'], [2, 3])) {
                                    $paymentDetail = $data['paymentDetail'];
                                    $this->apimodel->update_payment_mode((string) $companyProfile['vendorId'], $paymentDetail);
                                }

                                echo json_encode(array("Result" => true, 'Message' => 'Vendor created succesfully.'));
                            } else {
                                echo json_encode(array("Result" => true, 'Message' => 'Unable to save company data.'));
                            }
                        } else {
                            echo json_encode(array("Result" => false, 'Message' => 'Unable to save vendor data'));
                        }
                    } else {
                        echo json_encode(array("Result" => false, 'Message' => 'Email address is already registered.'));
                    }
                }
            }
        } catch (MongoDB\Driver\Exception\Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        } catch (Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        }
    }

    public function update_password() {
        try {
            if ($this->isLoggedIn()) {
                $data = $_POST;
                if ($data['userId'] != '') {
                    $password = $data['password'];
                    $data['password'] = password_hash($password, PASSWORD_BCRYPT);
                    $update = $this->adminmodel->editUser($data);

                    if ($update) {
                        echo json_encode(array("Result" => true, 'Message' => 'Password updated succesfully.'));
                    } else {
                        echo json_encode(array("Result" => false, 'Message' => 'Unable to update Password'));
                    }
                }
            }
        } catch (MongoDB\Driver\Exception\Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        } catch (Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        }
    }

    public function performUser() {
        try {
            $data = json_decode($this->input->raw_input_stream, true);
            if ($this->isLoggedIn()) {
                if ($data['userId'] != '') {

                    $udata['firstName'] = $data['firstName'];
                    $udata['userId'] = $data['userId'];
                    $udata['lastName'] = $data['lastName'];
                    $udata['ezListScreenName'] = $data['ezListScreenName'];
                    $udata['phoneNumber'] = $data['phoneNumber'];
                    $udata['countryCode'] = $data['countryCode'];
                    $udata['mobilePhone'] = $data['countryCode'] . $data['phoneNumber'];
                    $udata['address'] = $data['address'];
                    $udata['latitude'] = (float) $data['latitude'];
                    $udata['longitude'] = (float) $data['longitude'];
                    $udata['city'] = $data['city'];
                    $udata['state'] = $data['state'];
                    $udata['country'] = $data['country'];
                    $udata['email'] = $data['email'];
                    $udata['description'] = '';
                    $udata['speciality'] = '';
                    //$udata['userId'] = $data['userId'];
                    $udata['name'] = $data['name'];

                    $profileImageString = $data['profileImageString'];
                    if ($profileImageString != "") {
                        $new_file_name = time() . ".jpg";

                        $udata['profileImage'] = $new_file_name;

                        $file_upload_path = "./uploads/user_profile/" . $new_file_name;

                        file_put_contents($file_upload_path, base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $profileImageString)));
                    }
                    $update = $this->adminmodel->editUser($udata);

                    if ($update) {
                        echo json_encode(array("Result" => true, 'Message' => 'User updated succesfully.'));
                    } else {
                        echo json_encode(array("Result" => false, 'Message' => 'Unable to update user data'));
                    }
                } else {
                    $check_exists = $this->apimodel->check_email_exists($data['email']);
                    if (!$check_exists) {
                        $data['mobilePhone'] = '';
                        if ($data['phoneNumber'] != '') {
                            $data['mobilePhone'] = $data['countryCode'] . $data['phoneNumber'];
                        }

                        $data['userRole'] = (int) 1;
                        $data['registerFrom'] = 'admin';
                        //$data['address'] = "";
                        $data['isActive'] = true;
                        $data['isBuyer'] = true;
                        $data['isVendor'] = false;
                        $data['basicNotification'] = $this->basic_notification;
                        $data['otherNotification'] = $this->user_notification;
                        $data['quickBloxId'] = (int) 0;

                        $profileImageString = $data['profileImageString'];

                        unset($data['profileImageString']);

                        $new_file_name = time() . ".jpg";

                        $data['profileImage'] = $new_file_name;

                        $file_upload_path = "./uploads/user_profile/" . $new_file_name;

                        file_put_contents($file_upload_path, base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $profileImageString)));


                        $insertNew = $this->adminmodel->addUser($data);
                        if ($insertNew) {
                            echo json_encode(array("Result" => true, 'Message' => 'User created succesfully.'));
                        } else {
                            echo json_encode(array("Result" => false, 'Message' => 'User to save user data'));
                        }
                    } else {
                        echo json_encode(array("Result" => false, 'Message' => 'Email address is already registered.'));
                    }
                }
            }
        } catch (MongoDB\Driver\Exception\Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        } catch (Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        }
    }

    public function getParticularUser() {
        try {
            $data = json_decode($this->input->raw_input_stream, true);
            if ($this->isLoggedIn()) {
                $userData = $this->adminmodel->getUserDataById($data['_id']);
                if (!empty($userData)) {
                    echo json_encode(array("Result" => true, 'Message' => 'User data find succesfully', 'Data' => $userData));
                } else {
                    echo json_encode(array("Result" => false, 'Message' => 'Unable to find data of this user.'));
                }
            }
        } catch (MongoDB\Driver\Exception\Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        } catch (Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        }
    }

    /*
      Done by : 1081
      Description : This function to active/deactive users/vendor
      Createa at : 25/08/2017
     */

    public function toggleUser() {
        try {
            $userData = json_decode($this->input->raw_input_stream, true);
            if ($this->isLoggedIn()) {
                $toggleFlag = $this->adminmodel->toggleUser($userData['userId'], $userData['isActive']);
                if ($toggleFlag) {
                    echo json_encode(array("Result" => true, 'Message' => 'Succesfully toggle this user'));
                } else {
                    echo json_encode(array("Result" => false, 'Message' => 'Unable to toggle this user'));
                }
            }
        } catch (MongoDB\Driver\Exception\Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        } catch (Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        }
    }

    /*
      Done by : 1081
      Description : This function to delete users/vendor
      Created at : 28/08/2017
     */

    public function deleteUser() {
        try {
            $userData = json_decode($this->input->raw_input_stream, true);
            $userString = "vendor";
            $deleteFlag = false;
            if ($this->isLoggedIn()) {
                if ($userData['userRole'] == 1) {
                    $userString = "user";
                    $deleteFlag = $this->adminmodel->deleteUserData($userData['userId']);
                } else if ($userData['userRole'] == 2) {
                    $deleteFlag = $this->adminmodel->deleteVendorData($userData['userId']);
                }

                if ($deleteFlag) {
                    echo json_encode(array("Result" => true, 'Message' => 'Succesfully delete this ' . $userString));
                } else {
                    echo json_encode(array("Result" => false, 'Message' => 'Sorry!! unable to delete ' . $userString . '.'));
                }
            }
        } catch (MongoDB\Driver\Exception\Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        } catch (Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        }
    }

    public function sales_person() {
        if ($this->isLoggedIn()) {

            $header['active_page'] = "sales_person";

            $this->load->view('admin/header', $header);
            $data['referralCode'] = $this->adminmodel->generateReferralCode();

            $states = $this->apimodel->get_states();
            $statesList = ['' => 'Select'];
            if (!empty($states)) {
                foreach ($states as $eachState) {
                    $statesList[$eachState['_id']] = $eachState['name'];
                }
            }
            $data['states'] = $statesList;

            $this->load->view('admin/sales_person', $data);
            $this->load->view('admin/sales_person_js', array());
            $this->load->view('admin/footer');
        }
    }

    public function get_sales_persons_data() {
        $dataTable = $this->input->post(NULL, true);
        //echo "<pre>";print_r($dataTable);
        $draw = $dataTable['draw'];
        $start = $dataTable['start'];
        $length = $dataTable['length'];

        $orderData = $dataTable['order'];
        $orderKeyIndex = $orderData[0]['column'];
        $direction = $orderData[0]['dir'];
        $search = $dataTable['search']['value'];

        $keys = array('_id', 'name', 'email', 'phoneNumber', 'promoCode', 'action', 'createdDateTime');

        $totalSalesPersons = $this->adminmodel->getTotalSalesPersons($search);


        $directionInt = 1;
        if ($direction == 'desc') {
            $directionInt = -1;
        }

        if ($totalSalesPersons > 0) {
            $salesPersons = $this->adminmodel->getSalesPersons($start, $length, $search, $keys[$orderKeyIndex], $directionInt);

            $data = [];
            foreach ($salesPersons as $key => $eachSalesPerson) {
                $eachSalesPerson = (array) $eachSalesPerson;

                //echo "<pre>";print_r($eachSubCategory);
                //exit;
                $salesPersonId = (string) $eachSalesPerson['_id'];
                if ($eachSalesPerson['isActive']) {
                    $icon_class = "fa fa-eye text-green";
                    $to_do = false;
                    $display_title = "Deactivate salesperson";
                } else {
                    $icon_class = "fa fa-eye-slash text-danger";
                    $to_do = true;
                    $display_title = "Activate salesperson";
                }

                $data[] = array($salesPersonId, $eachSalesPerson['name'], $eachSalesPerson['email'], $eachSalesPerson['phoneNumber'], $eachSalesPerson['promoCode'], '<a class="edit_sales_person action_icon" salesPersonId="' . $salesPersonId . '" title="Edit sales person"><i class="fa fa-edit"></i></a><a class="toggle_activation_salesperson action_icon" salesPersonId="' . $salesPersonId . '" title="' . $display_title . '" toggle_Data = "' . $to_do . '"><i class="' . $icon_class . '"></i></a><a class="view_sales_person_vendors action_icon" salesPersonPromoCode="' . $eachSalesPerson['promoCode'] . '" salesPersonId="' . $salesPersonId . '" title="View vendors">View</a>', $eachSalesPerson['createdDateTime']);
            }
            echo json_encode(array('draw' => $draw, 'recordsTotal' => $totalSalesPersons, 'recordsFiltered' => $totalSalesPersons, 'data' => $data));
        } else {
            echo json_encode(array('draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => array()));
        }
    }

    public function performSalesPerson() {
        try {
            $salesPersonData = json_decode($this->input->raw_input_stream, true);
            if ($this->isLoggedIn()) {
                if (in_array($salesPersonData['paymentMethod'], [2, 3])) {
                    $salesPersonData['paymentDetail']['state'] = new MongoDB\BSON\ObjectID($salesPersonData['paymentDetail']['state']);
                    $salesPersonData['paymentDetail']['city'] = new MongoDB\BSON\ObjectID($salesPersonData['paymentDetail']['city']);
                }

                if ($salesPersonData['salesPersonId'] != '') {

                    $salesPersonData['phoneNumber'] = $salesPersonData['mobilePhone'];
                    $salesPersonData['mobilePhone'] = $salesPersonData['cc2'] . $salesPersonData['mobilePhone'];

                    unset($salesPersonData['cc2']);

                    $updateCat = $this->adminmodel->editSalesPerson($salesPersonData);
                    if ($updateCat) {
                        echo json_encode(array("Result" => true, 'Message' => 'Salesperson updated succesfully.'));
                    } else {
                        echo json_encode(array("Result" => false, 'Message' => 'Unable to save update salesperson data'));
                    }
                } else {
                    // insert sales person
                    $salesPersonData['phoneNumber'] = $salesPersonData['mobilePhone'];
                    $salesPersonData['mobilePhone'] = $salesPersonData['cc2'] . $salesPersonData['mobilePhone'];

                    unset($salesPersonData['cc2']);
                    unset($salesPersonData['salesPersonId']);

                    $insertNew = $this->adminmodel->addNewSalesPerson($salesPersonData);

                    $this->load->library('emailsmsnotification');

                    if ($insertNew) {
                        //Send sms to the salesperson
                        //$message = "Hello {$salesPersonData['name']}! Please use the email {$salesPersonData['email']} to register as a salesperson on the EZList.";
                        $message = sprintf("Hello %s! You are now signed up as a Salesperson on THEEZLIST! Please download the application from the link included in this text message, and register as a Salesperson using the email %s. Once you register on THEEZLIST, a unique Promo Code will appear on the screen that you will give to Service Providers to register on THEEZLIST application or website! %s", $salesPersonData['name'], $salesPersonData['email'], "https://itunes.apple.com/us/app/the-ez-list/id1305101269?ls=1&mt=8");
                        $flag = $this->emailsmsnotification->sendSms($salesPersonData['mobilePhone'], $message);
                    }

                    if ($insertNew) {
                        echo json_encode(array("Result" => true, 'Message' => 'New salesperson inserted succesfully.'));
                    } else {
                        echo json_encode(array("Result" => false, 'Message' => 'Unable to save salesperson data'));
                    }
                }
            }
        } catch (MongoDB\Driver\Exception\Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        } catch (Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        }
    }

    public function emailExists() {
        try {
            $salesPersonData = json_decode($this->input->raw_input_stream, true);
            $email = $salesPersonData['email'];
            $salespersonId = $salesPersonData['salespersonId'];


            $salespersonEmailExists = $this->apimodel->salesperson_check_email_exists($email, $salespersonId);
            if ($salespersonEmailExists) {
                echo json_encode(array("Result" => false, 'Message' => 'This email already exists!'));
            } else {
                echo json_encode(array("Result" => true, 'Message' => ''));
            }
        } catch (MongoDB\Driver\Exception\Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        } catch (Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        }
    }

    public function get_promo_code_vendors_data() {
        $dataTable = $this->input->post(NULL, true);
        $getData = $this->input->get(NULL, true);
        $promoCode = $getData['promoCode'];
        $salesPersonId = $getData['salesPersonId'];
        $salesPersonName = $getData['salesPersonName'];

        //echo "<pre>";print_r($dataTable);
        $draw = $dataTable['draw'];
        $start = $dataTable['start'];
        $length = $dataTable['length'];

        $orderData = $dataTable['order'];
        $orderKeyIndex = $orderData[0]['column'];
        $direction = $orderData[0]['dir'];
        $search = $dataTable['search']['value'];

        $keys = array('salespersonid', 'salespersonname', '_id', 'profileImage', 'name');

        $totalPromoCodeVendors = $this->adminmodel->getTotalSalesPersonVendors($search, $promoCode);


        $directionInt = 1;
        if ($direction == 'desc') {
            $directionInt = -1;
        }

        if ($totalPromoCodeVendors > 0) {
            $salesPersonVendors = $this->adminmodel->getSalesPersonVendors($start, $length, $search, $keys[$orderKeyIndex], $directionInt, $promoCode);

            $data = [];


            foreach ($salesPersonVendors as $key => $eachSalesPersonVendor) {
                $eachSalesPersonVendor = (array) $eachSalesPersonVendor;
                //echo "<pre>";print_r($eachSalesPersonVendor);
                $eachSalesPersonVendor['_id'] = (string) $eachSalesPersonVendor['_id'];
                $img = base_url() . 'uploads/category_images/placeholder.png';
                if ($eachSalesPersonVendor['profileImage'] != '') {
                    $img = base_url() . 'uploads/company_profile/' . $eachSalesPersonVendor['profileImage'];
                }


                $data[] = array(
                    $salesPersonId,
                    $salesPersonName,
                    $eachSalesPersonVendor['_id'],
                    '<div><img onerror=\'this.src="' . base_url('uploads/category_images/placeholder.png') . '"\' src="' . $img . '" width="100%" class="img-responsive img-thumbnail"></div>',
                    $eachSalesPersonVendor['name'],
                    '<a class="view_sales_person_services action_icon" vendorId="' . $eachSalesPersonVendor['_id'] . '" title="View services">View</a>'
                );
            }
            echo json_encode(array('draw' => $draw, 'recordsTotal' => $totalPromoCodeVendors, 'recordsFiltered' => $totalPromoCodeVendors, 'data' => $data));
        } else {
            echo json_encode(array('draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => array()));
        }
    }

    public function get_promo_code_services_data() {
        $dataTable = $this->input->post(NULL, true);
        $getData = $this->input->get(NULL, true);
        $vendorId = $getData['vendorId'];


        $draw = $dataTable['draw'];
        $start = $dataTable['start'];
        $length = $dataTable['length'];

        $orderData = $dataTable['order'];
        $orderKeyIndex = $orderData[0]['column'];
        $direction = $orderData[0]['dir'];
        $search = $dataTable['search']['value'];

        $keys = array('_id', 'serviceImage', 'serviceName', 'flatFee', 'Salesperson share', 'userName', 'salesPersonPaymentStatus');

        $totalPromoCodeServices = $this->adminmodel->getTotalSalesPersonServices($search, $vendorId);


        $directionInt = 1;
        if ($direction == 'desc') {
            $directionInt = -1;
        }

        if ($totalPromoCodeServices > 0) {
            $salesPersonServices = $this->adminmodel->getSalesPersonServices($start, $length, $search, $keys[$orderKeyIndex], $directionInt, $vendorId);

            $data1 = [];
            //$salesPersonServices = array_key_exists($promoCode, $salesPersonServices) ? $salesPersonServices[$promoCode] : [];

            foreach ($salesPersonServices as $key => $eachSalesPersonService) {
                $eachSalesPersonService = (array) $eachSalesPersonService;
                /* echo "<pre>";print_r($eachSalesPersonService);
                  exit; */
                $img = base_url() . 'uploads/category_images/placeholder.png';
                if ($eachSalesPersonService['serviceImage'] != '') {
                    $img = base_url() . 'uploads/service_images/' . $eachSalesPersonService['serviceImage'];
                }
                $serviceRequestId = (string) $eachSalesPersonService['_id'];

                $status = $eachSalesPersonService['salesPersonPaymentStatus'] == 0 ? "<button class='btn btn-success markaspaid' serviceRequestId='" . $serviceRequestId . "'>Mark as paid</button>" : "Paid";
                $data1[] = array(
                    $serviceRequestId,
                    '<div><img onerror=\'this.src="' . base_url('uploads/category_images/placeholder.png') . '"\' src="' . $img . '" width="100%" class="img-responsive img-thumbnail"></div>',
                    $eachSalesPersonService['serviceName'],
                    $eachSalesPersonService['flatFee'],
                    ($eachSalesPersonService['flatFee'] * 5) / 100,
                    $eachSalesPersonService['userName'],
                    $status
                );
            }
            echo json_encode(array('draw' => $draw, 'recordsTotal' => $totalPromoCodeServices, 'recordsFiltered' => $totalPromoCodeServices, 'data' => $data1));
        } else {
            echo json_encode(array('draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => array()));
        }
    }

    public function getSalesPersonPaymentInformation() {
        try {
            $getData = $this->input->get(NULL, true);

            //$postData = json_decode($postData, true);
            $salesPersonId = $getData['salesPersonId'];

            $paymentInformation = $this->adminmodel->getSalesPersonPaymentInformation($salesPersonId);
            $paymentInformation = (array) $paymentInformation;
            if (in_array($paymentInformation['paymentMethod'], [2, 3])) {
                //Get state and city
                $paymentInformation['paymentDetail'] = (array) $paymentInformation['paymentDetail'];
                $stateId = (string) $paymentInformation['paymentDetail']['state'];
                $cityId = (string) $paymentInformation['paymentDetail']['city'];
                $state = $this->adminmodel->getState($stateId);
                $state = $state['name'];
                $city = $this->adminmodel->getCity($cityId);
                $city = $city['city'];
                $paymentInformation['paymentDetail']['state'] = $state;
                $paymentInformation['paymentDetail']['city'] = $city;
                
            }



            echo json_encode(array('Result' => true, 'Data' => $paymentInformation));
        } catch (MongoDB\Driver\Exception\Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        } catch (Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        }
    }

    public function getVendorPaymentInformation() {
        try {
            $paymentData = json_decode($this->input->raw_input_stream, true);

            //$postData = json_decode($postData, true);
            $vendorId = $paymentData['vendorId'];
            $paymentMethod = $paymentData['paymentMethod'];

            $paymentInformation = $this->adminmodel->getVendorPaymentInformation($vendorId, $paymentMethod);
            $paymentInformation = (array) $paymentInformation;

            if (in_array($paymentMethod, [2, 3])) {
                //Get state and city
                $paymentInformation['paymentDetail'] = (array) $paymentInformation['paymentDetail'];
                $stateId = (string) $paymentInformation['paymentDetail']['state'];
                $cityId = (string) $paymentInformation['paymentDetail']['city'];
                $state = $this->adminmodel->getState($stateId);
                $state = $state['name'];
                $city = $this->adminmodel->getCity($cityId);
                $city = $city['city'];
                $paymentInformation['paymentDetail']['state'] = $state;
                $paymentInformation['paymentDetail']['city'] = $city;
            } else {
                $paymentInformation['paymentDetail']['paypalEmail'] = $paymentInformation['paypalEmail'];
            }
            $paymentInformation['paymentMethod'] = (int) $paymentMethod;


            echo json_encode(array('Result' => true, 'Data' => $paymentInformation));
        } catch (MongoDB\Driver\Exception\Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        } catch (Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        }
    }

    public function add_sales() {
        try {
            $header['active_page'] = "";

            $this->load->view('admin/header', $header);

            $data['referralCode'] = $this->adminmodel->generateReferralCode();
            $this->load->view('admin/add-sales-person', $data);
            $this->load->view('admin/footer');
        } catch (MongoDB\Driver\Exception\Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        } catch (Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        }
    }

    public function sales_report() {
        try {
            $header['active_page'] = "";

            $this->load->view('admin/header', $header);

            $this->load->view('admin/sales-person-report', array());
            $this->load->view('admin/footer');
        } catch (MongoDB\Driver\Exception\Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        } catch (Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        }
    }

    /*
      Done by : 1081
      Description : This function to payment listing
      Createa at : 28/11/2017
     */

    public function ServicePayment() {
        if ($this->isLoggedIn()) {

            $header['active_page'] = "ServicePayment";

            $this->load->view('admin/header', $header);

            $this->load->view('admin/service_payments', array());
            $this->load->view('admin/service_payments_js', array());
            $this->load->view('admin/footer');
        }
    }


    /*
      Done by : 1081
      Description : This function to get payments for service which is done already
      Createa at : 28/11/2017
     */

    public function getPaymentdata() {

        try {
            $dataTable = $this->input->post(NULL, true);
            //echo "<pre>";print_r($dataTable);
            $draw = $dataTable['draw'];
            $start = $dataTable['start'];
            $length = $dataTable['length'];

            $orderData = $dataTable['order'];
            $orderKeyIndex = $orderData[0]['column'];
            $direction = $orderData[0]['dir'];
            $search = $dataTable['search']['value'];

            $directionInt = 1;
            if ($direction == 'desc') {
                $directionInt = -1;
            }

            $keys = array('_id', 'paymentDateTime', 'vendorName', 'screenName', 'userName', 'flatFee', '20%', '80%', '4%', 'paypalEmail', 'rating', 'salesperson', 'promoCode', 'salespersonPaymentMethod', 'status');

            $totalCompany = $this->adminmodel->getTotalPayment($search);

            if ($totalCompany > 0) {
                $data = array();
                $payments = $this->adminmodel->getPayments($start, $length, $directionInt, $keys[$orderKeyIndex], $search);
                foreach ($payments as $key => $eachPayment) {
                    $eachPayment = (array) $eachPayment;
                    //var_export($eachPayment);die;
                    $paymentId = (string) $eachPayment['_id'];
                    $requestId = (string) $eachPayment['requestId'];
                    $promoCode = $eachPayment['promoCode'];
                    $promoCodeDiv = $promoCode != "" ? $promoCode : "<div class='text-center'>-</div>";
                    //$promoCodeDiv = $requestId;
                    $salesperson = $eachPayment['salesperson'] != "" ? $eachPayment['salesperson'] : "<div class='text-center'>-</div>";

                    $rating = $this->adminmodel->getRating($requestId);
                    if (!empty($rating)) {
                        $rating = (array) $rating;
                        $rating = (array) $rating['reviews'][0];


                        $serviceRating = "<div class='rating_star small'>" . $this->commonlib->printRatings($rating['rating']) . "</div>";
                    } else {
                        $serviceRating = "<div class='text-center'>-</div>";
                    }

                    $img = base_url() . 'uploads/user_profile/placeholder.png';
                    $status = "Pending";
                    $icon_class = "fa-credit-card";
                    $anchor_class = "make_payment_done";
                    $title = "Make Payment Done";
                    if ($eachPayment['status'] == 1) {
                        $status = "Paid";
                        $icon_class = "fa-check";
                        $anchor_class = "";
                        $title = "Payment is done";
                    }
                    $paymentMethodString = "<div class='text-center'>-</div>";
                    if ($eachPayment['paymentMethod'] == 1) {
                        $paymentMethodString = "Paypal";
                    } elseif ($eachPayment['paymentMethod'] == 2) {
                        $paymentMethodString = "Bank Account";
                    } elseif ($eachPayment['paymentMethod'] == 3) {
                        $paymentMethodString = "Pay by Check";
                    }

                    $paymentMethodInfo = $eachPayment['paymentMethod'] != 0 ? "<a class='action_icon view_payment_info' href='javascript:void(0)' vendor_id='" . $eachPayment['vendorId'] . "' payment_method=" . $eachPayment['paymentMethod'] . "><i class='fa fa-info-circle text-info' title='View Payment Method Information' style='cursor:pointer' ></i></a>" : "";

                    $salespersonPaymentMethodString = "<div class='text-center'>-</div>";
                    if ($eachPayment['salespersonPaymentMethod'] == 1) {
                        $salespersonPaymentMethodString = "Paypal";
                    } elseif ($eachPayment['salespersonPaymentMethod'] == 2) {
                        $salespersonPaymentMethodString = "Bank Account";
                    } elseif ($eachPayment['salespersonPaymentMethod'] == 3) {
                        $salespersonPaymentMethodString = "Pay by Check";
                    }

                    $salespersonPaymentMethodInfo = $eachPayment['salespersonPaymentMethod'] != 0 ? "<a class='action_icon view_salesperson_payment_info' href='javascript:void(0)' salesperson_id='" . $eachPayment['salespersonId'] . "' payment_method=" . $eachPayment['salespersonPaymentMethod'] . "><i class='fa fa-info-circle text-info' title='View Payment Method Information' style='cursor:pointer' ></i></a>" : "";

                    $eachPayment['userTimeZone'] = 'America/Los_Angeles';

                    $vendorName = ($eachPayment['vendorName']) ? $eachPayment['vendorName'] : '-';
                    $screenName = ($eachPayment['screenName']) ? $eachPayment['screenName'] : '-';
                    $userName = ($eachPayment['userName']) ? $eachPayment['userName'] : '-';
                    $flatFee = ($eachPayment['flatFee']) ? $eachPayment['flatFee'] : '0';

                    //Percentage
                    $twentyPercent = 0;
                    $eightyPercent = 0;
                    $fourPercent = 0;
                    if ($flatFee) {
                        $twentyPercent = ($flatFee * 20) / 100;
                        $twentyPercent = number_format($twentyPercent, 2);
                        $eightyPercent = ($flatFee * 80) / 100;
                        $eightyPercent = number_format($eightyPercent, 2);
                        $fourPercent = $promoCode != "" ? ($twentyPercent * 4) / 100 : 0;
                        $fourPercent = number_format($fourPercent, 2);
                    }

                    $paymentDateTime = "28-11-2017";
                    $vendorPaypalEmail = ($eachPayment['paypalEmail']) ? $eachPayment['paypalEmail'] : '-';
                    //echo "<pre>";print_r($eachPayment);
                    if (isset($eachPayment['paymentDateTime'])) {
                        $paymentDateTime = $eachPayment['paymentDateTime']->toDateTime();
                        $paymentDateTime->setTimeZone(new DateTimeZone($eachPayment['userTimeZone']));
                        $paymentDateTime = date('m/d/Y H:i:s', strtotime($paymentDateTime->format('Y-m-d H:i:s')));

                        $data[] = array(
                            $paymentId,
                            $paymentDateTime,
                            $vendorName,
                            $screenName,
                            $userName,
                            $flatFee,
                            $twentyPercent,
                            $eightyPercent,
                            $fourPercent,
                            $paymentMethodString . $paymentMethodInfo,
                            $serviceRating,
                            $salesperson,
                            $promoCodeDiv,
                            $salespersonPaymentMethodString . $salespersonPaymentMethodInfo,
                            $status,
                            '<a class="' . $anchor_class . ' action_icon" title="' . $title . '" payment-id="' . $paymentId . '"><i class="fa ' . $icon_class . ' text-info"></i></a>');
                    }
                }
                echo json_encode(array('draw' => $draw, 'recordsTotal' => $totalCompany, 'recordsFiltered' => $totalCompany, 'data' => $data));
            } else {
                echo json_encode(array('draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => array()));
            }
        } catch (MongoDB\Driver\Exception\Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        } catch (Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        }
    }

    /*
      Done by : 1081
      Description : This function to used to make service payment has been done to vendor
      Created at : 28/08/2017
     */

    public function payToVendor() {
        try {
            $paymentData = json_decode($this->input->raw_input_stream, true);

            $deleteFlag = $this->adminmodel->payVendorForService($paymentData['paymentId']);
            if ($deleteFlag) {
                echo json_encode(array("Result" => true, 'Message' => 'Vendor is marked as paid for the service successfully.'));
            } else {
                echo json_encode(array("Result" => false, 'Message' => 'Sorry!! unable to pay vendor for this service '));
            }
        } catch (MongoDB\Driver\Exception\Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        } catch (Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        }
    }

    public function getPaymentDetail() {
        try {
            $vendorData = json_decode($this->input->raw_input_stream, true);


            $paymentData = $this->adminmodel->getPaymentInfo($vendorData);
            if (!empty($paymentData)) {
                echo json_encode(array("Result" => true, 'Message' => 'Payment Information get successfully', 'data' => $paymentData));
            } else {
                echo json_encode(array("Result" => false, 'Message' => 'Sorry!! unable to get payment information'));
            }
        } catch (MongoDB\Driver\Exception\Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        } catch (Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        }
    }

    public function getNextSalesPersonPromoCode() {
        try {
            $nextPromoCode = $this->adminmodel->getNextPromoCode();

            $zeroes = 3 - strlen((string) $nextPromoCode);
            $padding = "";
            for ($i = 0; $i < $zeroes; $i++) {
                //echo $padding;
                $padding .= "0";
            }

            $nextPromoCode = "EZ" . $padding . $nextPromoCode;

            echo json_encode(array("Result" => true, 'Message' => '', 'data' => $nextPromoCode));
        } catch (MongoDB\Driver\Exception\Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        } catch (Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        }
    }

    public function makePaymentOfServiceRequest() {
        try {
            $requestData = json_decode($this->input->raw_input_stream, true);
            $serviceRequestId = $requestData['serviceRequestId'];
            $updateFlag = $this->adminmodel->makePaymentOfService($serviceRequestId);

            if ($updateFlag) {
                echo json_encode(array("Result" => true, 'Message' => 'This service request payment paid successfully.', 'data' => ''));
            } else {
                echo json_encode(array("Result" => false, 'Message' => '', 'data' => ''));
            }
        } catch (MongoDB\Driver\Exception\Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        } catch (Exception $e) {
            $msg = $e->getMessage(); // if any error occurres in syntax of mongodb query it gives us message.
            echo json_encode(array("Result" => false, 'Message' => $msg));
        }
    }

    public function export_excel() {
        $getData = $this->input->get();
        $userRole = $getData['userRole'];
        if (!in_array($userRole, [1, 2])) {
            die('Specifiy a user role');
        }

        if ($userRole == 1) {
            //Buyer
            $user_data = $this->get_user_data(true);
        } else {
            //Vendor
            $user_data = $this->get_vendors_data(true);
        }


        $this->load->library("excel");
        $object = new PHPExcel();

        $object->setActiveSheetIndex(0);
        $object->getActiveSheet()->getDefaultColumnDimension()->setWidth(25);

        $from = "A1"; // or any value
        $to = "J1"; // or any value
        $object->getActiveSheet()->getStyle("$from:$to")->getFont()->setBold(true);



        if ($userRole == 1) {
            $table_columns = array("Register Date/Time", "Buyer Image", "Name", "Email", "Street Address", "City", "State", "Phone", "Facebook Id", "Twitter Id");
            $filename = "Buyers Data";
        } else {
            $table_columns = array("Register Date/Time", "Vendor Image", "Name", "Email", "Street Address", "City", "State", "Phone", "Experience");
            $filename = "Vendors Data";
        }

        $column = 0;

        foreach ($table_columns as $field) {
            $object->getActiveSheet()->setCellValueByColumnAndRow($column, 1, $field);
            $column++;
        }



        $excel_row = 2;

        foreach ($user_data as $row) {
            $object->getActiveSheet()->setCellValueByColumnAndRow(0, $excel_row, $row[0]);
            $object->getActiveSheet()->setCellValueByColumnAndRow(1, $excel_row, $row[1]);
            $object->getActiveSheet()->setCellValueByColumnAndRow(2, $excel_row, $row[2]);
            $object->getActiveSheet()->setCellValueByColumnAndRow(3, $excel_row, $row[3]);
            $object->getActiveSheet()->setCellValueByColumnAndRow(4, $excel_row, $row[4]);
            $object->getActiveSheet()->setCellValueByColumnAndRow(5, $excel_row, $row[5]);
            $object->getActiveSheet()->setCellValueByColumnAndRow(6, $excel_row, $row[6]);
            $object->getActiveSheet()->setCellValueExplicit('H' . $excel_row, $row[7], PHPExcel_Cell_DataType::TYPE_STRING);
            //$object->getActiveSheet()->setCellValueByColumnAndRow(5, $excel_row, $row[5]);
            //$object->getActiveSheet()->setCellValueByColumnAndRow(6, $excel_row, (string)$row[6]);
            $object->getActiveSheet()->setCellValueExplicit('I' . $excel_row, $row[8], PHPExcel_Cell_DataType::TYPE_STRING);
            if ($userRole == 1) {
                //$object->getActiveSheet()->setCellValueByColumnAndRow(7, $excel_row, (string)$row[7]);
                $object->getActiveSheet()->setCellValueExplicit('J' . $excel_row, $row[9], PHPExcel_Cell_DataType::TYPE_STRING);
            }
            $excel_row++;
        }



        $object_writer = PHPExcel_IOFactory::createWriter($object, 'Excel5');
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename='{$filename}.xls'");
        $object_writer->save('php://output');
    }

    function pending_orders() {
        if ($this->isLoggedIn()) {

            $header['active_page'] = "pending_orders";

            $this->load->view('admin/header', $header);
            $data = ['order_type' => 'pending', 'title' => 'Pending Orders'];
            $this->load->view('admin/orders', $data);
            $this->load->view('admin/orders_js', $data);
            $this->load->view('admin/footer');
        }
    }

    function completed_orders() {
        if ($this->isLoggedIn()) {

            $header['active_page'] = "completed_orders";

            $this->load->view('admin/header', $header);
            $data = ['order_type' => 'completed', 'title' => 'Completed Orders'];
            $this->load->view('admin/orders', $data);
            $this->load->view('admin/orders_js', $data);
            $this->load->view('admin/footer');
        }
    }

    function cancelled_requests() {
        if ($this->isLoggedIn()) {

            $header['active_page'] = "cancelled_requests";

            $this->load->view('admin/header', $header);
            $data = ['order_type' => 'cancelled', 'title' => 'Cancelled Requests'];
            $this->load->view('admin/orders', $data);
            $this->load->view('admin/orders_js', $data);
            $this->load->view('admin/footer');
        }
    }

    public function get_orders() {
        $getData = $this->input->get(NULL, true);
        $dataTable = $this->input->post(NULL, true);



        $draw = $dataTable['draw'];
        $start = $dataTable['start'];
        $length = $dataTable['length'];

        $orderData = $dataTable['order'];
        $orderKeyIndex = $orderData[0]['column'];
        $direction = $orderData[0]['dir'];
        $search = $dataTable['search']['value'];

        $keys = array('createdDateTime', 'vendorName', 'buyerName', 'screenName', 'flatFee', 'appointmentTime', 'cancelReason', 'action');

        $orderType = $getData['type'];
        switch ($orderType) {
            case 'pending':
                $statuses = [0, 1, 2, 3, 4];
                $filter = [];
                break;
            case 'completed':
                $statuses = [5];
                $filter = ['isRemoved' => false];
                break;
            case 'cancelled':
                $statuses = [6];
                $filter = [];
                break;
        }

        $total = $this->adminmodel->getTotalOrders($search, $statuses, $filter);
        
        $directionInt = 1;
        if ($direction == 'desc') {
            $directionInt = -1;
        }

        if ($total > 0) {

            $orders = $this->adminmodel->getOrders($start, $length, $search, $keys[$orderKeyIndex], $directionInt, $statuses, $filter);

            foreach ($orders as $key => $eachorder) {
                $eachorder = (array) $eachorder;

//                echo "<pre>";print_r($eachorder);die;
                $requestId = (string) @$eachorder['_id'];


                $vendor = strlen($eachorder['vendorName']) > 0 ? $eachorder['vendorName'] : '-';
                $buyer = strlen($eachorder['buyerName']) > 0 ? $eachorder['buyerName'] : '-';
                $screenName = strlen($eachorder['screenName']) > 0 ? $eachorder['screenName'] : '-';
                $flatFee = $eachorder['flatFee'];
//                $cancelresion = $eachorder['cancelReason'];
                $cancelresion = ($eachorder['cancelReason']) ? $eachorder['cancelReason'] : '-';


                if (isset($eachorder['createdDatetime'])) {
                    $registeredDatetime = $eachorder['createdDatetime']->toDateTime();
                    $registeredDatetime->setTimeZone(new DateTimeZone('America/Los_Angeles'));
                    $registeredDatetime = date('m/d/Y H:i:s', strtotime($registeredDatetime->format('Y-m-d H:i:s')));
                }

                $appointmentTime = '-';

                if (isset($eachorder['serviceDate']) && $eachorder['serviceDate'] != null && $eachorder['serviceDate'] != '') {

                    $onlydate = $eachorder['serviceDate']->toDateTime();
                    $startime = $eachorder['serviceStartDateTime']->toDateTime();
                    $endTime = $eachorder['serviceEndDateTime']->toDateTime();
                    $dayfullName = $onlydate->format('l');
                    $appointmentTime = $dayfullName . ', ' . $startime->format('M dS h:i a') . ' - ' . $endTime->format('h:i a');
                }

                switch ($orderType) {
                    case 'pending':
                        $action = "<a class='cancel_request_admin action_icon' data-requestid='" . $requestId . "'  title='Cancel request'><i class='fa fa-remove text-danger'></i></a>";
                        break;
                    case 'completed':
                        $action = "<a class='delete_request action_icon' data-requestid='" . $requestId . "'  title='Delete request'><i class='fa fa-remove text-danger'></i></a>";
                        break;
                    case 'cancelled':
                        $action = "";
                        break;
                }

                $data[] = array(
                    $registeredDatetime,
                    $buyer,
                    $vendor,
                    $screenName,
                    $flatFee,
                    $appointmentTime,
                    $cancelresion,
                    $action);
            }
//            echo "<pre>";
//            print_r($data);

            echo json_encode(array('draw' => $draw, 'recordsTotal' => $total, 'recordsFiltered' => $total, 'data' => $data));
            
        } else {
            echo json_encode(array('draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => array()));
        }
    }

    public function cancel_request() {
        $postData = $this->input->post(NULL, true);
        $requestId = $postData['requestId'];
        $removeFlag = $this->adminmodel->cancelRequest($requestId);
        if ($removeFlag) {
            echo json_encode(['Result' => true, 'Message' => 'Request cancelled successfully.']);
        } else {
            echo json_encode(['Result' => false, 'Message' => 'Request could not be cancelled!']);
        }
    }

    public function remove_request() {
        $postData = $this->input->post(NULL, true);
        $requestId = $postData['requestId'];
        $removeFlag = $this->adminmodel->removeRequest($requestId);
        if ($removeFlag) {
            echo json_encode(['Result' => true, 'Message' => 'Request removed successfully.']);
        } else {
            echo json_encode(['Result' => false, 'Message' => 'Request could not be removed!']);
        }
    }

}
