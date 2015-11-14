alter table wrc_enrollment
   add numclasses tinyint unsigned,
   add shirtsize varchar(3) default null,
   add shirtcolor varchar(20) default null;
