/* Use "show create table" to ensure these are the constraints involving
   wrc_classes */

alter table wrc_reports drop foreign key wrc_reports_ibfk_2;
alter table wrc_strategy_report drop foreign key wrc_strategy_report_ibfk_2;
