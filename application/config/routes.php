<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/userguide3/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/

$route['default_controller'] = 'authcontroller/login';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

// ========================
// AUTHENTICATION ROUTES
// ========================
$route['logout'] = 'authcontroller/logout';
$route['register'] = 'authcontroller/register';

// ========================
// SCHOOL INFO ROUTES
// ========================
$route['school-info/form'] = 'schoolinfo/show_form';
$route['school-info/store'] = 'schoolinfo/store';
$route['school-info/get_school_districts'] = 'schoolinfo/get_school_districts';
$route['school-info/get_schools'] = 'schoolinfo/get_schools';

// ========================
// SUPER ADMIN ROUTES
// ========================
$route['superadmin'] = 'superadmincontroller/index';
$route['superadmin/dashboard'] = 'superadmincontroller/index';
$route['superadmin/add-user'] = 'superadmincontroller/add_user';
$route['superadmin/edit-user/(:num)'] = 'superadmincontroller/edit_user/$1';
$route['superadmin/update-role/(:num)'] = 'superadmincontroller/update_user_role/$1';
$route['superadmin/update-all-roles'] = 'superadmincontroller/update_all_roles';
$route['superadmin/delete-user/(:num)'] = 'superadmincontroller/delete_user/$1';

// ========================
// USER DASHBOARD ROUTES
// ========================
$route['users'] = 'user_dashboard_controller/index';
$route['users/dashboard'] = 'user_dashboard_controller/index';
$route['users/set_assessment_type'] = 'user_dashboard_controller/set_assessment_type';
$route['users/set_school_level'] = 'user_dashboard_controller/set_school_level';

// ========================
// USER PROFILE ROUTES  
// ========================
$route['profile'] = 'profile_controller/index';
$route['profile/update'] = 'profile_controller/update';


// ========================
// SBFP DASHBOARD ROUTES
// ========================
$route['sbfp'] = 'sbfp_dashboard_controller/index';
$route['sbfp/dashboard'] = 'sbfp_dashboard_controller/index';
$route['sbfp/create_section'] = 'sbfp_dashboard_controller/create_section';
$route['sbfp/remove_section'] = 'sbfp_dashboard_controller/remove_section';
$route['sbfp/assessment'] = 'sbfp_dashboard_controller/go_to_assessment';
$route['sbfp/view_assessments'] = 'sbfp_dashboard_controller/view_assessments';
$route['sbfp/export_assessments'] = 'sbfp_dashboard_controller/export_assessments';
$route['sbfp/statistics'] = 'sbfp_dashboard_controller/get_statistics';

// AJAX Routes
$route['sbfp/set_assessment_type'] = 'sbfp_dashboard_controller/set_assessment_type';
$route['sbfp/delete_assessment'] = 'sbfp_dashboard_controller/delete_assessment';
$route['sbfp/toggle_lock'] = 'sbfp_dashboard_controller/toggle_lock';
$route['sbfp/get_assessment_types'] = 'sbfp_dashboard_controller/get_assessment_types';
$route['sbfp/get_existing_data'] = 'sbfp_dashboard_controller/get_existing_data';

// ========================
// BACKWARD COMPATIBILITY
// ========================
$route['dashboard'] = 'superadmincontroller/index';
$route['dashboard/update_user_role/(:num)'] = 'superadmincontroller/update_user_role/$1';
$route['dashboard/update_all_roles'] = 'superadmincontroller/update_all_roles';
$route['dashboard/delete_user/(:num)'] = 'superadmincontroller/delete_user/$1';

// ========================
// OTHER MODULE ROUTES
// ========================
//$route['admin/districts'] = 'DistrictController/index';
//$route['admin/districts/create'] = 'DistrictController/create';
//$route['admin/districts/edit/(:num)'] = 'DistrictController/edit/$1';

// System Settings
//$route['settings'] = 'SettingsController/index';
//$route['settings/general'] = 'SettingsController/general';
//$route['settings/users'] = 'SettingsController/users';

// ========================
// NUTRITIONAL ASSESSMENT
// ========================
$route['assessments'] = 'nutritionalassessment/index';
$route['nutritional-assessment'] = 'nutritionalassessment/index';
$route['assessments/store'] = 'nutritionalassessment/store';
$route['assessments/bulk_store'] = 'nutritionalassessment/bulk_store';
$route['assessments/view_all'] = 'nutritionalassessment/view_all';

// ========================
// NUTRITIONAL ASSESSMENT REPORTS
// ========================
$route['admin/reports'] = 'nutritional_assessment_reports/index';   
$route['admin/reports/export'] = 'nutritional_assessment_reports/export';
$route['admin/reports/export_detail'] = 'nutritional_assessment_reports/export_detail';
$route['admin/reports/comparison_report'] = 'nutritional_assessment_reports/comparison_report';
$route['admin/reports/view_detail'] = 'nutritional_assessment_reports/view_detail';
$route['admin/reports/statistics'] = 'nutritional_assessment_reports/statistics';
$route['admin/reports/export_statistics'] = 'nutritional_assessment_reports/export_statistics';
$route['admin/reports/export_all_students'] = 'nutritional_assessment_reports/export_all_students';
$route['admin/reports/debug_template'] = 'nutritional_assessment_reports/debug_template';


// ========================
// EXCEL UPLOAD AND PROCESSING
// ========================
$route['excel_upload'] = 'excel_upload/index';
$route['excel_upload/upload_excel'] = 'excel_upload/upload_excel';
$route['excel_upload/clear_data'] = 'excel_upload/clear_data';

// ========================
// NUTRITIONAL UPLOAD
// ========================
$route['nutritional_upload'] = 'nutritional_upload/index';
$route['nutritional_upload/upload_nutritional_data'] = 'nutritional_upload/upload_nutritional_data';
$route['nutritional_upload/clear_nutritional_data'] = 'nutritional_upload/clear_nutritional_data';

// ========================
// USER NUTRITIONAL REPORTS
// ========================
$route['user/reports'] = 'nutritional_assessment_reports/index';
$route['user/reports/export'] = 'nutritional_assessment_reports/export';  
$route['user/reports/export_detail'] = 'nutritional_assessment_reports/export_detail';   
$route['user/reports/view_detail'] = 'nutritional_assessment_reports/view_detail';
$route['user/reports/statistics'] = 'nutritional_assessment_reports/statistics';

// ========================
// DISTRICT DASHBOARD ROUTES
// ========================
$route['district_dashboard'] = 'district_dashboard_controller/index';
$route['district_dashboard/get_school_details/(:any)'] = 'district_dashboard_controller/get_school_details/$1';

// ========================
//   DISTRICT REPORTS ROUTES
// ========================
$route['district/reports'] = 'district_reports_controller/index';
$route['district/reports/statistics'] = 'district_reports_controller/statistics';
$route['district/reports/export'] = 'district_reports_controller/export';
$route['district/reports/export_detail'] = 'district_reports_controller/export_detail';
$route['district/reports/export_statistics'] = 'district_reports_controller/export_statistics';
$route['district/reports/comparison_report'] = 'district_reports_controller/comparison_report';

// ========================
// DIVISION DASHBOARD ROUTES        
// ========================
$route['division_dashboard'] = 'division_dashboard_controller/index';
$route['division_dashboard/get_district_schools/(:any)'] = 'division_dashboard_controller/get_district_schools/$1';
$route['division_dashboard/get_school_details/(:any)'] = 'division_dashboard_controller/get_school_details/$1';

// ========================
//   DIVISION REPORTS ROUTES
// ========================
$route['division/reports'] = 'division_reports_controller/index';
$route['division/reports/statistics'] = 'division_reports_controller/statistics';
$route['division/reports/export'] = 'division_reports_controller/export';
$route['division/reports/export_detail'] = 'division_reports_controller/export_detail';
$route['division/reports/export_statistics'] = 'division_reports_controller/export_statistics';
$route['division/reports/comparison_report'] = 'division_reports_controller/comparison_report';

// ========================
// SBFP BENEFICIARIES ROUTES
// ========================
$route['sbfp_beneficiaries'] = 'sbfp_beneficiaries_controller/index';
$route['sbfp_beneficiaries/export_excel'] = 'sbfp_beneficiaries_controller/export_excel';
$route['sbfp_beneficiaries/print_report'] = 'sbfp_beneficiaries_controller/print_report';
$route['sbfp_beneficiaries/set_assessment_type'] = 'sbfp_beneficiaries_controller/set_assessment_type';
$route['sbfp_beneficiaries/set_school_level'] = 'sbfp_beneficiaries_controller/set_school_level';

// ========================
// ARCHIVE ROUTES
// ========================
$route['archive'] = 'archive_controller/index';
$route['archive/get_school_details'] = 'archive_controller/get_school_details';
$route['archive/process_archive'] = 'archive_controller/process_archive';
$route['archive/get_record_details/(:num)'] = 'archive_controller/get_record_details/$1';
$route['archive/restore_record/(:num)'] = 'archive_controller/restore_record/$1';
$route['archive/export'] = 'archive_controller/export_archive';

