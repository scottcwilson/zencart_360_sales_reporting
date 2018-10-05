CREATE TABLE direct_deposit (
  deposit_id int(11) NOT NULL auto_increment,
  deposit_date date NOT NULL, 
  name varchar(80) NOT NULL default '',
  email_address varchar(96) NOT NULL default '',
  amount decimal(15,4) NOT NULL default '0.0000',
  PRIMARY KEY  (deposit_id),
  KEY idx_email (email_address)
) ENGINE=MyISAM;
