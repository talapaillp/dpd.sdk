create table IF NOT EXISTS b_ipol_dpd_location (
	ID INTEGER PRIMARY KEY AUTOINCREMENT,
	
	COUNTRY_CODE varchar(255) null,
	COUNTRY_NAME varchar(255) null,
	
	REGION_CODE varchar(255) null,
	REGION_NAME varchar(255) null,
	
	CITY_ID bigint UNSIGNED NOT NULL default '0',
	CITY_CODE varchar(255) null,
	CITY_NAME varchar(255) null,
	CITY_ABBR varchar(255) null,
	
	LOCATION_ID int not null default '0',

	IS_CASH_PAY char(1) not null default 'N',

	ORIG_NAME varchar(255) null,
	ORIG_NAME_LOWER varchar(255) null
);

CREATE INDEX IF NOT EXISTS b_ipol_dpd_location_crc ON b_ipol_dpd_location (CITY_NAME, REGION_NAME, COUNTRY_NAME);
CREATE INDEX IF NOT EXISTS b_ipol_dpd_location_search_text ON b_ipol_dpd_location (ORIG_NAME_LOWER);