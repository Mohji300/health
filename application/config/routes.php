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

$route['default_controller'] = 'SuperAdminController';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

// ========================
// AUTHENTICATION ROUTES
// ========================
$route['login'] = 'AuthController/login';
$route['logout'] = 'AuthController/logout';
$route['register'] = 'AuthController/register';

// ========================
// SCHOOL INFO ROUTES
// ========================
$route['school-info/form'] = 'SchoolInfo/show_form';
$route['school-info/store'] = 'SchoolInfo/store';
$route['school-info/get_school_districts'] = 'SchoolInfo/get_school_districts';
$route['school-info/get_schools'] = 'SchoolInfo/get_schools';

// ========================
// SUPER ADMIN ROUTES
// ========================
$route['superadmin'] = 'SuperAdminController/index';
$route['superadmin/dashboard'] = 'SuperAdminController/index';
$route['superadmin/add-user'] = 'SuperAdminController/add_user';
$route['superadmin/edit-user/(:num)'] = 'SuperAdminController/edit_user/$1';
$route['superadmin/update-role/(:num)'] = 'SuperAdminController/update_user_role/$1';
$route['superadmin/update-all-roles'] = 'SuperAdminController/update_all_roles';
$route['superadmin/delete-user/(:num)'] = 'SuperAdminController/delete_user/$1';

// ========================
// USER DASHBOARD ROUTES
// ========================
$route['user'] = 'UserDashboard/index';
$route['user/dashboard'] = 'UserDashboard/index';

// ========================
// USER PROFILE ROUTES  
// ========================
$route['profile'] = 'Profile_controller/index';
$route['profile/update'] = 'Profile_controller/update';


// ========================
// SBFP DASHBOARD ROUTES
// ========================
$route['sbfp'] = 'SbfpDashboard/index';
$route['sbfp/dashboard'] = 'SbfpDashboard/index';
$route['sbfp/create_section'] = 'SbfpDashboard/create_section';
$route['sbfp/remove_section'] = 'SbfpDashboard/remove_section';
$route['sbfp/assessment'] = 'SbfpDashboard/go_to_assessment';
$route['sbfp/view_assessments'] = 'SbfpDashboard/view_assessments';
$route['sbfp/export_assessments'] = 'SbfpDashboard/export_assessments';
$route['sbfp/statistics'] = 'SbfpDashboard/get_statistics';

// ========================
// BACKWARD COMPATIBILITY
// ========================
$route['dashboard'] = 'SuperAdminController/index';
$route['dashboard/update_user_role/(:num)'] = 'SuperAdminController/update_user_role/$1';
$route['dashboard/update_all_roles'] = 'SuperAdminController/update_all_roles';
$route['dashboard/delete_user/(:num)'] = 'SuperAdminController/delete_user/$1';

// ========================
// OTHER MODULE ROUTES
// ========================
// District Management
$route['admin/districts'] = 'DistrictController/index';
$route['admin/districts/create'] = 'DistrictController/create';
$route['admin/districts/edit/(:num)'] = 'DistrictController/edit/$1';

// System Settings
$route['settings'] = 'SettingsController/index';
$route['settings/general'] = 'SettingsController/general';
$route['settings/users'] = 'SettingsController/users';

// ========================
// NUTRITIONAL ASSESSMENT
// ========================
$route['assessments'] = 'NutritionalAssessment/index';
$route['nutritional-assessment'] = 'NutritionalAssessment/index';
$route['assessments/store'] = 'NutritionalAssessment/store';
$route['assessments/bulk_store'] = 'NutritionalAssessment/bulk_store';
$route['assessments/view_all'] = 'NutritionalAssessment/view_all';

// ========================
// NUTRITIONAL ASSESSMENT REPORTS
// ========================
$route['admin/reports'] = 'Nutritional_assessment_reports/index';
$route['admin/reports/export'] = 'Nutritional_assessment_reports/export';
$route['admin/reports/export_detail'] = 'Nutritional_assessment_reports/export_detail';
$route['admin/reports/comparison_report'] = 'Nutritional_assessment_reports/comparison_report';
$route['admin/reports/view_detail'] = 'Nutritional_assessment_reports/view_detail';
$route['admin/reports/statistics'] = 'Nutritional_assessment_reports/statistics';
$route['admin/reports/export_statistics'] = 'Nutritional_assessment_reports/export_statistics';
$route['admin/reports/export_all_students'] = 'Nutritional_assessment_reports/export_all_students';

// ========================
// EXCEL UPLOAD AND PROCESSING
// ========================
$route['excel_upload'] = 'Excel_upload/index';
$route['excel_upload/upload_excel'] = 'Excel_upload/upload_excel';
$route['excel_upload/clear_data'] = 'Excel_upload/clear_data';

// ========================
// NUTRITIONAL UPLOAD
// ========================
$route['nutritional_upload'] = 'Nutritional_upload/index';
$route['nutritional_upload/upload_nutritional_data'] = 'Nutritional_upload/upload_nutritional_data';
$route['nutritional_upload/clear_nutritional_data'] = 'Nutritional_upload/clear_nutritional_data';

// ========================
// USER NUTRITIONAL REPORTS
// ========================
$route['reports'] = 'Nutritional_assessment_reports/index';
$route['reports/export'] = 'Nutritional_assessment_reports/export';     
$route['reports/view_detail'] = 'Nutritional_assessment_reports/view_detail';
$route['reports/statistics'] = 'Nutritional_assessment_reports/statistics';

// ========================
// DISTRICT DASHBOARD ROUTES
// ========================
$route['district_dashboard'] = 'District_dashboard_controller/index';
$route['district_dashboard/get_school_details/(:any)'] = 'District_dashboard_controller/get_school_details/$1';

// ========================
//   DISTRICT REPORTS ROUTES
// ========================
$route['district/reports'] = 'District_reports_controller/index';
$route['district/reports/statistics'] = 'District_reports_controller/statistics';
$route['district/reports/export'] = 'District_reports_controller/export';
$route['district/reports/export_detail'] = 'District_reports_controller/export_detail';
$route['district/reports/export_statistics'] = 'District_reports_controller/export_statistics';
$route['district/reports/comparison_report'] = 'District_reports_controller/comparison_report';

// ========================
// DIVISION DASHBOARD ROUTES        
// ========================
$route['division_dashboard'] = 'Division_dashboard_controller/index';
$route['division_dashboard/get_district_schools/(:any)'] = 'Division_dashboard_controller/get_district_schools/$1';
$route['division_dashboard/get_school_details/(:any)'] = 'Division_dashboard_controller/get_school_details/$1';

// ========================
//   DIVISION REPORTS ROUTES
// ========================
$route['division/reports'] = 'Division_reports_controller/index';
$route['division/reports/statistics'] = 'Division_reports_controller/statistics';
$route['division/reports/export'] = 'Division_reports_controller/export';
$route['division/reports/export_detail'] = 'Division_reports_controller/export_detail';
$route['division/reports/export_statistics'] = 'Division_reports_controller/export_statistics';
$route['division/reports/comparison_report'] = 'Division_reports_controller/comparison_report';


