SET NAMES utf8;SET SQL_MODE='';SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO';/*Data for the table `state_fields`*/insert into `state_fields`(`id`,`state_id`,`field_name`) values (19,1,'type');insert into `state_fields`(`id`,`state_id`,`field_name`) values (20,1,'number');insert into `state_fields`(`id`,`state_id`,`field_name`) values (21,1,'start');insert into `state_fields`(`id`,`state_id`,`field_name`) values (22,1,'end');insert into `state_fields`(`id`,`state_id`,`field_name`) values (23,1,'contact');insert into `state_fields`(`id`,`state_id`,`field_name`) values (24,1,'contact_address');insert into `state_fields`(`id`,`state_id`,`field_name`) values (25,1,'notes');insert into `state_fields`(`id`,`state_id`,`field_name`) values (26,2,'signature');insert into `state_fields`(`id`,`state_id`,`field_name`) values (27,3,'signature_2');insert into `state_fields`(`id`,`state_id`,`field_name`) values (46,4,'name');insert into `state_fields`(`id`,`state_id`,`field_name`) values (47,4,'department');insert into `state_fields`(`id`,`state_id`,`field_name`) values (48,4,'type');insert into `state_fields`(`id`,`state_id`,`field_name`) values (49,4,'purpose');insert into `state_fields`(`id`,`state_id`,`field_name`) values (50,4,'destination');insert into `state_fields`(`id`,`state_id`,`field_name`) values (51,4,'departure');insert into `state_fields`(`id`,`state_id`,`field_name`) values (52,4,'departure_time');insert into `state_fields`(`id`,`state_id`,`field_name`) values (53,4,'arrival');insert into `state_fields`(`id`,`state_id`,`field_name`) values (54,4,'arrival_time');insert into `state_fields`(`id`,`state_id`,`field_name`) values (55,4,'means');insert into `state_fields`(`id`,`state_id`,`field_name`) values (56,4,'preferences');insert into `state_fields`(`id`,`state_id`,`field_name`) values (57,4,'notes');insert into `state_fields`(`id`,`state_id`,`field_name`) values (58,5,'signature');insert into `state_fields`(`id`,`state_id`,`field_name`) values (59,6,'signature_2');insert into `state_fields`(`id`,`state_id`,`field_name`) values (60,6,'accommodation');insert into `state_fields`(`id`,`state_id`,`field_name`) values (61,6,'flight');insert into `state_fields`(`id`,`state_id`,`field_name`) values (62,6,'road');insert into `state_fields`(`id`,`state_id`,`field_name`) values (63,6,'other');insert into `state_fields`(`id`,`state_id`,`field_name`) values (74,7,'item');insert into `state_fields`(`id`,`state_id`,`field_name`) values (75,7,'specifications');insert into `state_fields`(`id`,`state_id`,`field_name`) values (76,7,'purpose');insert into `state_fields`(`id`,`state_id`,`field_name`) values (77,7,'quantity');insert into `state_fields`(`id`,`state_id`,`field_name`) values (78,7,'price');insert into `state_fields`(`id`,`state_id`,`field_name`) values (79,7,'required');insert into `state_fields`(`id`,`state_id`,`field_name`) values (80,7,'suggested');insert into `state_fields`(`id`,`state_id`,`field_name`) values (81,7,'notes');insert into `state_fields`(`id`,`state_id`,`field_name`) values (82,8,'signature');insert into `state_fields`(`id`,`state_id`,`field_name`) values (83,9,'signature_2');insert into `state_fields`(`id`,`state_id`,`field_name`) values (92,10,'amount');insert into `state_fields`(`id`,`state_id`,`field_name`) values (93,10,'purpose');insert into `state_fields`(`id`,`state_id`,`field_name`) values (94,10,'date');insert into `state_fields`(`id`,`state_id`,`field_name`) values (95,10,'date_to');insert into `state_fields`(`id`,`state_id`,`field_name`) values (96,11,'signature');insert into `state_fields`(`id`,`state_id`,`field_name`) values (97,12,'signature_2');insert into `state_fields`(`id`,`state_id`,`field_name`) values (98,12,'amount_issued');insert into `state_fields`(`id`,`state_id`,`field_name`) values (99,12,'date_issued');insert into `state_fields`(`id`,`state_id`,`field_name`) values (107,13,'item');insert into `state_fields`(`id`,`state_id`,`field_name`) values (108,13,'description');insert into `state_fields`(`id`,`state_id`,`field_name`) values (109,13,'quantity');insert into `state_fields`(`id`,`state_id`,`field_name`) values (110,14,'signature');insert into `state_fields`(`id`,`state_id`,`field_name`) values (111,15,'signature_2');insert into `state_fields`(`id`,`state_id`,`field_name`) values (112,15,'quantity_issued');insert into `state_fields`(`id`,`state_id`,`field_name`) values (113,15,'date');insert into `state_fields`(`id`,`state_id`,`field_name`) values (122,16,'room');insert into `state_fields`(`id`,`state_id`,`field_name`) values (123,16,'purpose');insert into `state_fields`(`id`,`state_id`,`field_name`) values (124,16,'date');insert into `state_fields`(`id`,`state_id`,`field_name`) values (125,16,'time');insert into `state_fields`(`id`,`state_id`,`field_name`) values (126,16,'duration');insert into `state_fields`(`id`,`state_id`,`field_name`) values (127,16,'recurring');insert into `state_fields`(`id`,`state_id`,`field_name`) values (128,16,'additional');insert into `state_fields`(`id`,`state_id`,`field_name`) values (129,17,'signature');insert into `state_fields`(`id`,`state_id`,`field_name`) values (137,18,'amount');insert into `state_fields`(`id`,`state_id`,`field_name`) values (138,18,'description');insert into `state_fields`(`id`,`state_id`,`field_name`) values (139,18,'account');insert into `state_fields`(`id`,`state_id`,`field_name`) values (140,19,'signature');insert into `state_fields`(`id`,`state_id`,`field_name`) values (141,20,'signature_2');insert into `state_fields`(`id`,`state_id`,`field_name`) values (142,20,'amount_approved');insert into `state_fields`(`id`,`state_id`,`field_name`) values (143,20,'date');/*Data for the table `workflow`*/insert into `workflow`(`id`,`form_builder_formid`,`process`,`display_name`,`code`,`description`,`department_id`,`date_created`,`active`) values (1,1,'leave_request_form','Leave Request Form','LRF','to be filled by employee when applying for leave',0,'1288559360',1);insert into `workflow`(`id`,`form_builder_formid`,`process`,`display_name`,`code`,`description`,`department_id`,`date_created`,`active`) values (2,2,'travel_request_form','Travel Request Form','TRF','',0,'1288559393',1);insert into `workflow`(`id`,`form_builder_formid`,`process`,`display_name`,`code`,`description`,`department_id`,`date_created`,`active`) values (3,3,'purchase_request_form','Purchase Request Form','PRF','To be filled by employee when requesting the purchase of an item',0,'1288559402',1);insert into `workflow`(`id`,`form_builder_formid`,`process`,`display_name`,`code`,`description`,`department_id`,`date_created`,`active`) values (4,4,'cash_advance_form','Cash Advance Form','CAF','',0,'1288559410',1);insert into `workflow`(`id`,`form_builder_formid`,`process`,`display_name`,`code`,`description`,`department_id`,`date_created`,`active`) values (5,5,'materials_requisition_form','Materials Requisition Form','MRF','to be filled by employee when requesting materials such as papers, ink etc',0,'1288559419',1);insert into `workflow`(`id`,`form_builder_formid`,`process`,`display_name`,`code`,`description`,`department_id`,`date_created`,`active`) values (6,6,'meeting_room_request','Meeting Room Request','MRR','',0,'1288559428',1);insert into `workflow`(`id`,`form_builder_formid`,`process`,`display_name`,`code`,`description`,`department_id`,`date_created`,`active`) values (7,7,'cash_reimbursement_request','Cash Reimbursement Request','CRR','to be filled by employees requesting reimbursement of personal cash spent on official expenses',0,'1288559443',1);/*Data for the table `workflow_states`*/insert into `workflow_states`(`id`,`form_builder_sectionid`,`name`,`display_name`,`process_id`,`position`,`duration`,`sla_email`,`departmental`,`locational`,`allow_approver_change`,`is_approval`,`date_created`) values (1,0,'section_1','Section 1',1,0,24,'',0,0,0,0,'1288559349');insert into `workflow_states`(`id`,`form_builder_sectionid`,`name`,`display_name`,`process_id`,`position`,`duration`,`sla_email`,`departmental`,`locational`,`allow_approver_change`,`is_approval`,`date_created`) values (2,3,'supervisor','Supervisor',1,1,24,'',0,0,1,1,'1288559360');insert into `workflow_states`(`id`,`form_builder_sectionid`,`name`,`display_name`,`process_id`,`position`,`duration`,`sla_email`,`departmental`,`locational`,`allow_approver_change`,`is_approval`,`date_created`) values (3,4,'hr','HR',1,2,72,'',0,0,0,1,'1288559360');insert into `workflow_states`(`id`,`form_builder_sectionid`,`name`,`display_name`,`process_id`,`position`,`duration`,`sla_email`,`departmental`,`locational`,`allow_approver_change`,`is_approval`,`date_created`) values (4,0,'section_1','Section 1',2,0,24,'',0,0,0,0,'1288559392');insert into `workflow_states`(`id`,`form_builder_sectionid`,`name`,`display_name`,`process_id`,`position`,`duration`,`sla_email`,`departmental`,`locational`,`allow_approver_change`,`is_approval`,`date_created`) values (5,5,'manager','Manager',2,1,24,'',0,0,1,1,'1288559393');insert into `workflow_states`(`id`,`form_builder_sectionid`,`name`,`display_name`,`process_id`,`position`,`duration`,`sla_email`,`departmental`,`locational`,`allow_approver_change`,`is_approval`,`date_created`) values (6,6,'finance_cost_implications','Finance (Cost Implications)',2,2,24,'',0,0,0,1,'1288559393');insert into `workflow_states`(`id`,`form_builder_sectionid`,`name`,`display_name`,`process_id`,`position`,`duration`,`sla_email`,`departmental`,`locational`,`allow_approver_change`,`is_approval`,`date_created`) values (7,0,'section_1','Section 1',3,0,24,'',0,0,0,0,'1288559401');insert into `workflow_states`(`id`,`form_builder_sectionid`,`name`,`display_name`,`process_id`,`position`,`duration`,`sla_email`,`departmental`,`locational`,`allow_approver_change`,`is_approval`,`date_created`) values (8,7,'manager','Manager',3,1,24,'',0,0,1,1,'1288559402');insert into `workflow_states`(`id`,`form_builder_sectionid`,`name`,`display_name`,`process_id`,`position`,`duration`,`sla_email`,`departmental`,`locational`,`allow_approver_change`,`is_approval`,`date_created`) values (9,8,'section','Section 3',3,2,72,'',0,0,0,1,'1288559402');insert into `workflow_states`(`id`,`form_builder_sectionid`,`name`,`display_name`,`process_id`,`position`,`duration`,`sla_email`,`departmental`,`locational`,`allow_approver_change`,`is_approval`,`date_created`) values (10,0,'section_1','Section 1',4,0,24,'',0,0,0,0,'1288559410');insert into `workflow_states`(`id`,`form_builder_sectionid`,`name`,`display_name`,`process_id`,`position`,`duration`,`sla_email`,`departmental`,`locational`,`allow_approver_change`,`is_approval`,`date_created`) values (11,11,'manager','Manager',4,1,24,'',0,0,1,1,'1288559410');insert into `workflow_states`(`id`,`form_builder_sectionid`,`name`,`display_name`,`process_id`,`position`,`duration`,`sla_email`,`departmental`,`locational`,`allow_approver_change`,`is_approval`,`date_created`) values (12,12,'finance','Finance',4,2,24,'',0,0,0,1,'1288559410');insert into `workflow_states`(`id`,`form_builder_sectionid`,`name`,`display_name`,`process_id`,`position`,`duration`,`sla_email`,`departmental`,`locational`,`allow_approver_change`,`is_approval`,`date_created`) values (13,0,'section_1','Section 1',5,0,24,'',0,0,0,0,'1288559418');insert into `workflow_states`(`id`,`form_builder_sectionid`,`name`,`display_name`,`process_id`,`position`,`duration`,`sla_email`,`departmental`,`locational`,`allow_approver_change`,`is_approval`,`date_created`) values (14,9,'supervisor','Supervisor',5,1,24,'',0,0,1,1,'1288559419');insert into `workflow_states`(`id`,`form_builder_sectionid`,`name`,`display_name`,`process_id`,`position`,`duration`,`sla_email`,`departmental`,`locational`,`allow_approver_change`,`is_approval`,`date_created`) values (15,10,'finance','Finance',5,2,72,'',0,0,0,1,'1288559419');insert into `workflow_states`(`id`,`form_builder_sectionid`,`name`,`display_name`,`process_id`,`position`,`duration`,`sla_email`,`departmental`,`locational`,`allow_approver_change`,`is_approval`,`date_created`) values (16,0,'section_1','Section 1',6,0,24,'',0,0,0,0,'1288559427');insert into `workflow_states`(`id`,`form_builder_sectionid`,`name`,`display_name`,`process_id`,`position`,`duration`,`sla_email`,`departmental`,`locational`,`allow_approver_change`,`is_approval`,`date_created`) values (17,13,'manager','Manager',6,1,24,'',0,0,1,1,'1288559428');insert into `workflow_states`(`id`,`form_builder_sectionid`,`name`,`display_name`,`process_id`,`position`,`duration`,`sla_email`,`departmental`,`locational`,`allow_approver_change`,`is_approval`,`date_created`) values (18,0,'section_1','Section 1',7,0,24,'',0,0,0,0,'1288559442');insert into `workflow_states`(`id`,`form_builder_sectionid`,`name`,`display_name`,`process_id`,`position`,`duration`,`sla_email`,`departmental`,`locational`,`allow_approver_change`,`is_approval`,`date_created`) values (19,14,'supervisor','Supervisor',7,1,24,'',0,0,1,1,'1288559443');insert into `workflow_states`(`id`,`form_builder_sectionid`,`name`,`display_name`,`process_id`,`position`,`duration`,`sla_email`,`departmental`,`locational`,`allow_approver_change`,`is_approval`,`date_created`) values (20,15,'finance','Finance',7,2,48,'',0,0,0,1,'1288559443');SET SQL_MODE=@OLD_SQL_MODE;SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;