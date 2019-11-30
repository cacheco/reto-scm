<?php
ini_set('display_errors','1');

class GestionConf{

    var $connectDb;
    var $hasValue;
	var $result;
	var $consulta;

    function __construct($connectDb){
        $this->connectDb = $connectDb;
        $this->hasValue = false;
		$this->result = [];
    }
	
	function getTables() {
        $this->result = array();
        try {
            if ($this->connectDb->Conectarse() == true) {
                $params = array();

                $query = "SELECT name FROM sys.objects where type = 'U' and is_ms_shipped = 0";
				
                $this->consulta = $this->connectDb->conect->prepare($query);
                $this->consulta->execute($params);
                $this->consulta->setFetchMode(PDO::FETCH_OBJ);

                while ($row = $this->consulta->fetch()) {
                    $this->hasValue = true;
                    $this->result[] = $row;
                }

                $this->connectDb->Desconectarse();
            }
        }
        catch (PDOException $e) {
            print_r($e);
            $this->set_error($e);
            return false;
        }

        return true;
    }
	
	function getTableDefinition( $tableName ){
        $this->result = array();

        try{
            if( $this->connectDb->Conectarse() == true ){

                $return_value = 0;

                $this->consulta = $this->connectDb->conect->prepare("EXEC sp_helptable ?");
                $this->consulta->bindParam(1, $tableName, PDO::PARAM_STR);

                $this->consulta->execute();

                while($row = $this->consulta->fetch(PDO::FETCH_ASSOC)) {
					$this->result = $row;
				}

                $this->connectDb->Desconectarse();
            }
        }catch( PDOException $e ){
            print_r($e);
            $this->set_error( $e );
            return false;
        }
        return true;
    }
	
	function getObjectsDefinition() {
        $this->result = array();
        try {
            if ($this->connectDb->Conectarse() == true) {
                $params = array();

                $query = "SELECT  s.name SchemaName
							   ,o.name RoutineName
							   ,o.[type] RoutineType
							   ,procs.*
							FROM    sys.sql_modules procs
							INNER JOIN sys.objects o ON procs.object_id = o.object_id
							INNER JOIN sys.schemas s ON o.schema_id = s.schema_id;";
				
                $this->consulta = $this->connectDb->conect->prepare($query);
                $this->consulta->execute($params);
                $this->consulta->setFetchMode(PDO::FETCH_OBJ);

                while ($row = $this->consulta->fetch()) {
                    $this->hasValue = true;
                    $this->result[] = $row;
                }

                $this->connectDb->Desconectarse();
            }
        }
        catch (PDOException $e) {
            print_r($e);
            $this->set_error($e);
            return false;
        }

        return true;
    }
	
	function validateSpHelpTableExist() {
        $this->result = array();
        try {
            if ($this->connectDb->Conectarse() == true) {
                $params = array();

                $query = "SELECT  *
								FROM    sys.objects
								WHERE   object_id = OBJECT_ID(N'sp_helptable')
										AND type IN ( N'P', N'PC' )";
				
                $this->consulta = $this->connectDb->conect->prepare($query);
                $this->consulta->execute($params);
                $this->consulta->setFetchMode(PDO::FETCH_OBJ);

                while ($row = $this->consulta->fetch()) {
                    $this->hasValue = true;
                    $this->result[] = $row;
                }

                $this->connectDb->Desconectarse();
            }
        }
        catch (PDOException $e) {
            print_r($e);
            $this->set_error($e);
            return false;
        }

        return true;
    }
	
	function getConfigurationTable( $tableName ) {
        $this->result = array();
        try {
            if ($this->connectDb->Conectarse() == true) {
                $params = array();

                $query = "SELECT  *
								FROM    ".$tableName.";";
				
                $this->consulta = $this->connectDb->conect->prepare($query);
                $this->consulta->execute($params);
                $this->consulta->setFetchMode(PDO::FETCH_OBJ);

                while ($row = $this->consulta->fetch()) {
                    $this->hasValue = true;
                    $this->result[] = $row;
                }

                $this->connectDb->Desconectarse();
            }
        }
        catch (PDOException $e) {
            print_r($e);
            $this->set_error($e);
            return false;
        }

        return true;
    }
	
	function crearSpHelpTable(){
        $this->rows_updated = 0;

        try{
            if( $this->connectDb->Conectarse() == true ){

                $params = [];

                $query = "/*************************************************************
							** File:     [sp_helptable]
							** Author:   D Prasad Sahoo
							** https://www.sqlindia.com/
							** Description: To generate table definition or text
									Includes: 1. Table script
										 2. Primary key
										 3. Foreign keys
										 4. Default constraints
										 5. Check constraints
										 6. unique and non clustered indexes
							** Date:   05/24/2015
							**************************************************************
							** Change History
							**************************************************************
							** PR   Date        Author              Change Description
							** --   --------    -------             ----------------------
							** 1    05/24/2015  Prasad Sahoo        Created
							**************************************************************/
							CREATE PROC sp_helptable (
							@tableName sysname = NULL --The table name for which the script will be genrated else for all tables
							, @schemaName sysname = NULL --Specify the schema name else table of same name from all schema will be genrated
							, @includeDBName BIT = 1 -- Do you want to include use database name in the definition script?
							, @includeForeignKey BIT = 1 -- Do you want to include foreignkeys in the definition script?
							, @includeDefault BIT = 1 --Do you want to include default constraints in the definition script?
							, @includeCheck BIT = 1 --Do you want to include check constraints in the definition script?
							, @includeIndex BIT = 1 --Do you want to include non-clustered and unique indices in the definition script?
							)
							AS
							BEGIN
							SET NOCOUNT ON
							SET TRANSACTION ISOLATION LEVEL READ UNCOMMITTED
							--=====================Variable Declaration===================
							DECLARE
							@idxTableName SYSNAME,
							@idxTableID INT,
							@idxname SYSNAME,
							@idxid INT,
							@colCount INT,
							@colCountMinusIncludedColumns INT,
							@IxColumn SYSNAME,
							@IxFirstColumn BIT,
							@ColumnIDInTable INT,
							@ColumnIDInIndex INT,
							@IsIncludedColumn INT,
							@sIncludeCols VARCHAR (MAX),
							@sIndexCols VARCHAR (MAX),
							@sSQL VARCHAR (MAX),
							@dSQL VARCHAR (MAX),
							@sParamSQL VARCHAR (MAX),
							@sFilterSQL VARCHAR (MAX),
							@location SYSNAME,
							@IndexCount INT,
							@CurrentIndex INT,
							@CurrentCol INT,
							@Name VARCHAR (128),
							@IsPrimaryKey TINYINT,
							@Fillfactor INT,
							@FilterDefinition VARCHAR (MAX),
							@IsClustered BIT

							DECLARE @i INT, @init INT
							DECLARE @MaxColOrder INT
							DECLARE @cols VARCHAR (MAX) = '', @default VARCHAR (MAX) = '', @check VARCHAR (MAX) = '', @fk VARCHAR (MAX) = '', @idx VARCHAR (MAX) = ''
							DECLARE @var varchar (max)

							IF OBJECT_ID ('tempdb..#TableScript') IS NOT NULL DROP TABLE #TableScript
							CREATE TABLE #TableScript (ID INT IDENTITY (1, 1)
							, SchemaName varchar (255)
							, TableName varchar (255)
							, TableScript varchar (max)
							, ForeignKey VARCHAR(MAX)
							, DefaultConstraint varchar(max)
							, CheckConstraint varchar(max)
							, Indexes varchar(max)
							)

							IF OBJECT_ID ('tempdb..#IndexSQL') IS NOT NULL DROP TABLE #IndexSQL
							CREATE TABLE #IndexSQL
							(TableName VARCHAR (128) NOT NULL
							, IndexName VARCHAR (128) NOT NULL
							, IsClustered BIT NOT NULL
							, IsPrimaryKey BIT NOT NULL
							, IndexCreateSQL VARCHAR (max) NOT NULL
							, IndexDROPSQL varchar (max)
							)

							IF OBJECT_ID ('tempdb..#IndexListing') IS NOT NULL DROP TABLE [#IndexListing]
							CREATE TABLE #IndexListing
							(
							[IndexListingID] INT IDENTITY (1, 1) PRIMARY KEY CLUSTERED,
							[SchemaName] VARCHAR (255),
							[TableName] SYSNAME COLLATE SQL_Latin1_General_CP1_CI_AS NULL,
							[ObjectID] INT NOT NULL,
							[IndexName] SYSNAME COLLATE SQL_Latin1_General_CP1_CI_AS NULL,
							[IndexID] INT NOT NULL,
							[IsPrimaryKey] TINYINT NOT NULL,
							[FillFactor] INT,
							[FilterDefinition] NVARCHAR (MAX) NULL
							)

							IF OBJECT_ID ('tempdb..#ColumnListing') IS NOT NULL DROP TABLE [#ColumnListing]
							CREATE TABLE #ColumnListing
							(
							[ColumnListingID] INT IDENTITY (1, 1) PRIMARY KEY CLUSTERED,
							[ColumnIDInTable] INT NOT NULL,
							[Name] SYSNAME COLLATE SQL_Latin1_General_CP1_CI_AS NULL,
							[ColumnIDInIndex] INT NOT NULL,
							[IsIncludedColumn] BIT NULL
							)
							--=====================Variable Declaration===================

							INSERT INTO #TableScript (schemaName, tableName)
								SELECT
									TABLE_SCHEMA,
									TABLE_NAME
								FROM INFORMATION_SCHEMA.TABLES
								WHERE TABLE_SCHEMA = COALESCE(@schemaName, TABLE_SCHEMA)
								AND TABLE_NAME = COALESCE(@tableName, TABLE_NAME)
								AND TABLE_TYPE = 'BASE TABLE'

							SELECT
								@i = COUNT(1),
								@init = 1
							FROM #TableScript

							WHILE @i > = @init
							BEGIN
							SELECT
								@tableName = tableName,
								@schemaName = schemaName
							FROM #TableScript
							WHERE ID = @init

							SELECT
								@MaxColOrder = MAX(ORDINAL_POSITION)
							FROM INFORMATION_SCHEMA.COLUMNS
							WHERE TABLE_NAME = @tableName AND TABLE_SCHEMA = @schemaName

							SET @var = CASE WHEN @includeDBName = 1 THEN 'USE ' + QUOTENAME(DB_NAME()) ELSE '' END +
							'
							GO
							/****** Object:  Table ' + QUOTENAME(@schemaName) + '.' + QUOTENAME(@tableName) + ' ******/
							SET ANSI_NULLS ON
							GO

							SET QUOTED_IDENTIFIER ON
							GO

							'
							SELECT @var = @var + 'CREATE TABLE ' + QUOTENAME(@schemaName) + '.' + QUOTENAME(@tableName) + '('
							;
							WITH cteCol as (SELECT
								QUOTENAME(sys_col.name) + SPACE(1) + (SELECT TOP 1
									CASE
										WHEN sys_types.is_user_defined = 1 THEN QUOTENAME(SCHEMA_NAME(sys_types.schema_id)) + '.' + QUOTENAME(sys_types.name) ELSE QUOTENAME(sys_types.name)
									END
								FROM sys.types sys_types
								WHERE sys_col.system_type_id = sys_types.system_type_id AND sys_types.user_type_id = sys_col.user_type_id)
								+ CASE (SELECT TOP 1
									sys_types.name
								FROM sys.types sys_types
								WHERE sys_col.system_type_id = sys_types.system_type_id)
									WHEN 'decimal' THEN '(' + CAST(sys_col.precision AS varchar(10)) + ', ' + CAST(sys_col.scale AS varchar(10)) + ')'
									WHEN 'numeric' THEN '(' + CAST(sys_col.precision AS varchar(10)) + ', ' + CAST(sys_col.scale AS varchar(10)) + ')'
									WHEN 'sql_variant' THEN ''
									WHEN 'text' THEN ''
									WHEN 'ntext' THEN ''
									WHEN 'xml' THEN '' ELSE COALESCE(
								CASE
									WHEN sys_col.max_length = -1 THEN '(MAX)'
									WHEN sys_col.user_type_id <> sys_col.system_type_id THEN '' ELSE (SELECT TOP 1
									ISNULL('(' + CAST(inf_cols.CHARACTER_MAXIMUM_LENGTH AS varchar(10)) + ')', '')
								FROM INFORMATION_SCHEMA.COLUMNS inf_cols
								WHERE inf_tbl.TABLE_SCHEMA = inf_cols.TABLE_SCHEMA AND inf_tbl.TABLE_NAME = inf_cols.TABLE_NAME AND sys_col.name = inf_cols.COLUMN_NAME)
								END, '')
								END
								+ SPACE(1) + CASE
									WHEN sys_col.is_identity = 1 THEN (SELECT TOP 1
										'IDENTITY(' + CAST(ISNULL(sys_ident.seed_value, 0) AS varchar(10)) + ', ' + CAST(ISNULL(sys_ident.increment_value, 1) AS varchar(10)) + ')'
									FROM sys.identity_columns sys_ident
									WHERE sys_ident.name = sys_col.name AND sys_col.object_id = sys_ident.object_id AND sys_col.is_identity = 1)
								ELSE ''
								END
								+ SPACE(1) + CASE
									WHEN sys_col.is_nullable = 1 THEN 'NULL' ELSE 'NOT NULL'
								END + CASE
									WHEN @MaxColOrder = sys_col.column_id THEN '' ELSE ', '
								END AS cols,
								sys_col.column_id
							FROM INFORMATION_SCHEMA.TABLES inf_tbl
							INNER JOIN sys.columns sys_col
								ON inf_tbl.TABLE_NAME = OBJECT_NAME(sys_col.object_id)
							WHERE inf_tbl.TABLE_NAME = @tableName AND inf_tbl.TABLE_SCHEMA = @schemaName
							)

							SELECT
								@cols = COALESCE(@cols, '') + ISNULL(CHAR(13) + CHAR(10) + c.cols, '')
							FROM cteCol c
							ORDER BY COLUMN_ID

							SELECT
								@var = @var + @cols + '
							)
							GO
							'
							--=========================Index=========================
							INSERT INTO #IndexListing ([SchemaName], [TableName], [ObjectID], [IndexName], [IndexID], [IsPrimaryKey], [FILLFACTOR], [FilterDefinition])
								SELECT
									OBJECT_SCHEMA_NAME(si.object_id),
									OBJECT_NAME(si.object_id),
									si.object_id,
									si.name,
									si.index_id,
									si.Is_Primary_Key,
									si.Fill_Factor,
									si.filter_definition
								FROM sys.indexes si
								LEFT OUTER JOIN information_schema.table_constraints tc
									ON si.name = tc.constraint_name AND OBJECT_NAME(si.object_id) = tc.table_name
								WHERE OBJECTPROPERTY(si.object_id, 'IsUserTable') = 1 AND OBJECT_NAME(si.object_id) = @tableName AND OBJECT_SCHEMA_NAME(si.object_id) = @schemaName
								ORDER BY OBJECT_NAME(si.object_id), si.index_id

							SELECT
								@IndexCount = COUNT(1),
								@CurrentIndex = 1
							FROM #IndexListing

							WHILE @CurrentIndex < = @IndexCount
							BEGIN
							SELECT
								@idxTableName = [TableName],
								@idxTableID = [ObjectID],
								@idxname = [IndexName],
								@idxid = [IndexID],
								@IsPrimaryKey = [IsPrimaryKey],
								@FillFactor = [FILLFACTOR],
								@FilterDefinition = [FilterDefinition],
								@schemaName = [SchemaName]
							FROM #IndexListing
							WHERE [IndexListingID] = @CurrentIndex

							IF (@IsPrimaryKey = 1)
							BEGIN
							SET @sSQL = 'ALTER TABLE [' + @schemaName + '].[' + @idxTableName + '] ADD CONSTRAINT [' + @idxname + '] PRIMARY KEY '

							-- Check if the index is clustered
							IF (INDEXPROPERTY (@idxTableID, @idxname, 'IsClustered') = 0)
							BEGIN
							SET @sSQL = @sSQL + 'NON'
							SET @IsClustered = 0
							END
							ELSE
							BEGIN
							SET @IsClustered = 1
							END
							SET @sSQL = @sSQL + 'CLUSTERED' + CHAR(13) + '(' + CHAR(13)
							END
							ELSE
							BEGIN
							SET @sSQL = 'CREATE '

							-- Check if the index is unique
							IF (INDEXPROPERTY (@idxTableID, @idxname, 'IsUnique') = 1)
							BEGIN
							SET @sSQL = 'ALTER TABLE [' + @schemaName + '].[' + @idxTableName + '] ADD CONSTRAINT [' + @idxname + '] UNIQUE NONCLUSTERED'
							END

							-- Check if the index is clustered
							IF (INDEXPROPERTY (@idxTableID, @idxname, 'IsClustered') = 1)
							BEGIN
							SET @sSQL = @sSQL + 'CLUSTERED '
							SET @IsClustered = 1
							END
							ELSE
							BEGIN
							SET @IsClustered = 0
							END

							SELECT
								@sSQL =
									CASE
										WHEN INDEXPROPERTY(@idxTableID, @idxname, 'IsUnique') <> 1
										THEN @sSQL + 'INDEX [' + @idxname + '] ON [' + @schemaName + '].[' + @idxTableName + ']' + CHAR(13) + '(' + CHAR(13)
										ELSE @sSQL + CHAR(13) + '(' + CHAR(13)
									END,
								@colCount = 0,
								@colCountMinusIncludedColumns = 0
							END

							-- Get the nuthe mber of cols in the index
							SELECT
								@colCount = COUNT(*),
								@colCountMinusIncludedColumns = SUM(CASE ic.is_included_column
									WHEN 0 THEN 1 ELSE 0
								END)
							FROM sys.index_columns ic
							INNER JOIN sys.columns sc
								ON ic.object_id = sc.object_id AND ic.column_id = sc.column_id
							WHERE ic.object_id = @idxtableid AND index_id = @idxid

							-- Get the file group info
							SELECT
								@location = f.[name]
							FROM sys.indexes i
							INNER JOIN sys.filegroups f
								ON i.data_space_id = f.data_space_id
							INNER JOIN sys.all_objects o
								ON i.[object_id] = o.[object_id]
							WHERE o.object_id = @idxTableID AND i.index_id = @idxid AND o.schema_id = SCHEMA_ID(@schemaName)

							-- Get all columns of the index
							INSERT INTO #ColumnListing ([ColumnIDInTable], [Name], [ColumnIDInIndex], [IsIncludedColumn])
								SELECT
									sc.column_id,
									sc.name,
									ic.index_column_id,
									ic.is_included_column
								FROM sys.index_columns ic
								INNER JOIN sys.columns sc
									ON ic.object_id = sc.object_id AND ic.column_id = sc.column_id
								WHERE ic.object_id = @idxTableID AND index_id = @idxid
								ORDER BY ic.index_column_id

							IF @@ROWCOUNT > 0
							BEGIN

							SELECT
								@CurrentCol = 1
							SELECT
								@IxFirstColumn = 1,
								@sIncludeCols = '',
								@sIndexCols = ''

							WHILE @CurrentCol < = @ColCount
							BEGIN
							SELECT
								@ColumnIDInTable = ColumnIDInTable,
								@Name = Name,
								@ColumnIDInIndex = ColumnIDInIndex,
								@IsIncludedColumn = IsIncludedColumn
							FROM #ColumnListing
							WHERE [ColumnListingID] = @CurrentCol

							IF @IsIncludedColumn = 0
							BEGIN

							SELECT
								@sIndexCols = CHAR(9) + @sIndexCols + '[' + @Name + '] '

							-- Check the sort order of the index cols ????????
							IF (INDEXKEY_PROPERTY (@idxTableID, @idxid, @ColumnIDInIndex, 'IsDescending')) = 0
							BEGIN
							SET @sIndexCols = @sIndexCols + ' ASC '
							END
							ELSE
							BEGIN
							SET @sIndexCols = @sIndexCols + ' DESC '
							END

							IF @CurrentCol < @colCountMinusIncludedColumns
							BEGIN
							SET @sIndexCols = @sIndexCols + ', '
							END

							END
							ELSE
							BEGIN
							-- Check for any include columns
							IF LEN (@sIncludeCols) > 0
							BEGIN
							SET @sIncludeCols = @sIncludeCols + ','
							END

							SELECT
								@sIncludeCols = @sIncludeCols + '[' + @Name + ']'

							END

							SET @CurrentCol = @CurrentCol + 1
							END

							TRUNCATE TABLE #ColumnListing
							--append to the result
							IF LEN (@sIncludeCols) > 0
							SET @sIndexCols = @sSQL + @sIndexCols + CHAR(13) + ') ' + ' INCLUDE ( ' + @sIncludeCols + ' ) '
							ELSE
							SET @sIndexCols = @sSQL + @sIndexCols + CHAR(13) + ') '

							-- Add filtering
							IF @FilterDefinition IS NOT NULL
							SET @sFilterSQL = ' WHERE ' + @FilterDefinition + ' ' + CHAR(13)
							ELSE
							SET @sFilterSQL = ''

							-- Build the options
							SET @sParamSQL = 'WITH ( PAD_INDEX = '

							IF INDEXPROPERTY (@idxTableID, @idxname, 'IsPadIndex') = 1
							SET @sParamSQL = @sParamSQL + 'ON,'
							ELSE
							SET @sParamSQL = @sParamSQL + 'OFF,'

							SET @sParamSQL = @sParamSQL + ' ALLOW_PAGE_LOCKS = '

							IF INDEXPROPERTY (@idxTableID, @idxname, 'IsPageLockDisallowed') = 0
							SET @sParamSQL = @sParamSQL + 'ON,'
							ELSE
							SET @sParamSQL = @sParamSQL + 'OFF,'

							SET @sParamSQL = @sParamSQL + ' ALLOW_ROW_LOCKS = '

							IF INDEXPROPERTY (@idxTableID, @idxname, 'IsRowLockDisallowed') = 0
							SET @sParamSQL = @sParamSQL + 'ON,'
							ELSE
							SET @sParamSQL = @sParamSQL + 'OFF,'

							SET @sParamSQL = @sParamSQL + ' STATISTICS_NORECOMPUTE = '

							-- THIS DOES NOT WORK PROPERLY; IsStatistics only says what generated the last set, not what it was set to do.
							IF (INDEXPROPERTY (@idxTableID, @idxname, 'IsStatistics') = 1)
							SET @sParamSQL = @sParamSQL + 'ON'
							ELSE
							SET @sParamSQL = @sParamSQL + 'OFF'

							SET @sParamSQL =
								CASE
									WHEN @IsPrimaryKey <> 1 AND @Fillfactor <> 0 THEN @sParamSQL + ' ,FILLFACTOR = ' + CAST(ISNULL(NULLIF(@Fillfactor, 0), 80) AS varchar(3)) ELSE @sParamSQL
								END

							IF (@IsPrimaryKey = 1) OR (INDEXPROPERTY (@idxTableID, @idxname, 'IsUnique') = 1) -- DROP_EXISTING isn't valid for PK's
							BEGIN
							SET @sParamSQL = @sParamSQL + ' ) '
							END
							ELSE
							BEGIN
							SET @sParamSQL = @sParamSQL + ' ) '
							END

							SET @sSQL = @sIndexCols + CHAR(13) + @sFilterSQL + CHAR(13) + @sParamSQL

							SET @sSQL = @sSQL + ' ON [' + @location + ']' + CHAR(13) + CHAR(10) + 'GO' + CHAR(13) + CHAR(10)

							INSERT INTO #IndexSQL (TableName, IndexName, IsClustered, IsPrimaryKey, IndexCreateSQL, IndexDROPSQL)
								SELECT
									@idxTableName,
									@idxName,
									@IsClustered,
									@IsPrimaryKey,
									@sSQL,
									CASE
										WHEN INDEXPROPERTY(@idxTableID, @idxname, 'IsUnique') = 1 OR INDEXPROPERTY(@idxTableID, @idxname, 'IsClustered') = 1 THEN 'ALTER TABLE [dbo].[' + @idxTableName + '] DROP CONSTRAINT [' + @idxname + ']' + CHAR(13) + CHAR(10) + 'GO' + CHAR(13) + CHAR(10) ELSE 'DROP INDEX [' + @idxname + '] ON [dbo].[' + @idxTableName + ']' + CHAR(13) + CHAR(10) + 'GO' + CHAR(13) + CHAR(10)
									END
							END

							SET @CurrentIndex = @CurrentIndex + 1
							END
							--SELECT IndexCreateSQL FROM #IndexSQL ORDER BY IsPrimaryKey DESC, IsClustered DESC

							SELECT
								@idx = COALESCE(@idx, '') + ISNULL(CHAR(13) + CHAR(10) + i.IndexCreateSQL, '')
							FROM #IndexSQL i
							WHERE IsPrimaryKey = 1
							ORDER BY IsPrimaryKey DESC, IsClustered DESC

							SELECT
								@var = @var + @idx --Primary Key included

							UPDATE #TableScript
							SET Indexes = @idx
							WHERE ID = @init
							--=============================================================

							--======================Default Constraint=====================
							;
							WITH cteDefault AS (
							SELECT
								'ALTER TABLE ' + QUOTENAME(SCHEMA_NAME(def.schema_id)) + '.' + QUOTENAME(OBJECT_NAME(parent_object_id)) + ' ADD CONSTRAINT ' + QUOTENAME(name) + ' DEFAULT ' + [definition] + ' FOR ' + QUOTENAME(COL_NAME(parent_object_id, parent_column_id)) + '
							GO
							' AS [default]
							FROM sys.default_constraints def
							WHERE is_system_named = 0 AND OBJECT_NAME(def.parent_object_id) = @tableName AND SCHEMA_NAME(def.schema_id) = @schemaName)

							SELECT
								@default = COALESCE(@default, '') + ISNULL(CHAR(13) + CHAR(10) + d.[default], '')
							FROM cteDefault d

							IF @includeDefault = 1
							SELECT @var = @var + @default

							UPDATE #TableScript
							SET DefaultConstraint = @default
							WHERE ID = @init
							--======================Default Constraint=====================

							--======================Check Constraint=======================
							;
							WITH cteCheck AS (
							SELECT
								'ALTER TABLE ' + QUOTENAME(SCHEMA_NAME(SCHEMA_ID)) + '.' + QUOTENAME(OBJECT_NAME(parent_object_id)) + ' WITH CHECK ADD  CONSTRAINT ' + QUOTENAME(name) + ' CHECK (' + [definition] + ')' + '
							GO

							ALTER TABLE ' + QUOTENAME(SCHEMA_NAME(SCHEMA_ID)) + '.' + QUOTENAME(OBJECT_NAME(parent_object_id)) + ' CHECK CONSTRAINT ' + QUOTENAME(name) + '
							GO
							' AS [check]
							FROM sys.check_constraints
							WHERE is_system_named = 0 AND OBJECT_NAME(parent_object_id) = @tableName AND SCHEMA_NAME(SCHEMA_ID) = @schemaName)

							SELECT
								@check = COALESCE(@check, '') + ISNULL(CHAR(13) + CHAR(10) + c.[check], '')
							FROM cteCheck c

							IF @includeCheck = 1
							SELECT @var = @var + @check

							UPDATE #TableScript
							SET CheckConstraint = @check
							WHERE ID = @init
							--======================Check Constraint=======================

							--===================ForeignKey Constraint=====================
							;
							WITH cteForeignKey AS (
							SELECT
								ROW_NUMBER() OVER (ORDER BY parent_object_id DESC) AS id,
								SCHEMA_NAME(SCHEMA_ID) AS schemaName,
								OBJECT_NAME(parent_object_id) AS tableName,
								name AS fkName,
								OBJECT_SCHEMA_NAME(referenced_object_id) AS refSchemaName,
								OBJECT_NAME(referenced_object_id) AS refTablename,
								OBJECT_ID AS fkObjectID,
								is_disabled,
								is_not_for_replication,
								is_not_trusted,
								delete_referential_action,
								update_referential_action,
								fk_cols = STUFF((SELECT
									',' + COL_NAME(sfk.parent_object_id, fkc.parent_column_id)
								FROM sys.foreign_key_columns fkc
								WHERE fkc.constraint_object_id = sfk.object_id
								FOR xml PATH (''))
								, 1, 1, ''),
								pk_cols = STUFF((SELECT
									',' + COL_NAME(sfk.referenced_object_id, fkc.referenced_column_id)
								FROM sys.foreign_key_columns fkc
								WHERE fkc.constraint_object_id = sfk.object_id
								FOR xml PATH (''))
								, 1, 1, '')
							FROM sys.foreign_keys sfk
							WHERE OBJECT_NAME(parent_object_id) = @tableName)
							, cte as (
							SELECT
								'ALTER TABLE '
								+ QUOTENAME(schemaName) + '.' + QUOTENAME(tableName)
								+ CASE is_not_trusted
									WHEN 0 THEN ' WITH CHECK ' ELSE ' WITH NOCHECK '
								END
								+ ' ADD CONSTRAINT ' + QUOTENAME(fkName)
								+ ' FOREIGN KEY (' + fk_Cols + ' ) ' + CHAR(13) + CHAR(10)
								+ 'REFERENCES ' + QUOTENAME(refSchemaName) + '.' + QUOTENAME(refTableName)
								+ ' (' + pk_Cols + ')'
								+ ' ON UPDATE ' + CASE update_referential_action
									WHEN 0 THEN 'NO ACTION '
									WHEN 1 THEN 'CASCADE '
									WHEN 2 THEN 'SET NULL ' ELSE 'SET DEFAULT '
								END
								+ ' ON DELETE ' + CASE delete_referential_action
									WHEN 0 THEN 'NO ACTION '
									WHEN 1 THEN 'CASCADE '
									WHEN 2 THEN 'SET NULL ' ELSE 'SET DEFAULT '
								END
								+ CASE is_not_for_replication
									WHEN 1 THEN ' NOT FOR REPLICATION ' ELSE ''
								END + ';'
								+ CHAR(13) + CHAR(10) + 'GO' + CHAR(13) + CHAR(10)
								+ 'ALTER TABLE '
								+ QUOTENAME(schemaName) + '.' + QUOTENAME(tableName)
								+ CASE is_disabled
									WHEN 0 THEN ' CHECK ' ELSE ' NOCHECK '
								END
								+ 'CONSTRAINT ' + QUOTENAME(fkName)
								+ ';'
								+ CHAR(13) + CHAR(10) + 'GO' + CHAR(13) + CHAR(10) + CHAR(13) + CHAR(10) AS fk
							FROM cteForeignKey)

							SELECT
								@fk = COALESCE(@fk, '') + ISNULL(CHAR(13) + CHAR(10) + f.fk, '')
							FROM cte f

							IF @includeForeignKey = 1
							SELECT @var = @var + @fk

							UPDATE #TableScript
							SET ForeignKey = @fk
							WHERE ID = @init
							--===================ForeignKey Constraint=====================

							--===========================Indexes===========================
							SET @idx = ''
							SELECT
								@idx = COALESCE(@idx, '') + ISNULL(CHAR(13) + CHAR(10) + i.IndexCreateSQL, '')
							FROM #IndexSQL i
							WHERE IsPrimaryKey = 0
							ORDER BY IsPrimaryKey DESC, IsClustered DESC

							IF @includeIndex = 1
							SELECT @var = @var + @idx --Unique and Non-clustered indexes included

							UPDATE #TableScript
							SET Indexes = Indexes + @idx
							WHERE ID = @init
							--===========================Indexes===========================

							--=========Final Update========
							UPDATE #TableScript
							SET tableScript = @var + '
							SET ANSI_NULLS OFF
							GO

							SET QUOTED_IDENTIFIER OFF
							GO'
							WHERE ID = @init
							--=========Final Update========

							SET @init += 1
							SELECT
								@var = '',
								@cols = '',
								@default = '',
								@check = '',
								@fk = '',
								@idx = ''

							TRUNCATE TABLE #IndexSQL
							TRUNCATE TABLE #ColumnListing
							TRUNCATE TABLE #IndexListing
							END --Main loop end

							SELECT
								*
							FROM #TableScript

							SET NOCOUNT OFF
							SET TRANSACTION ISOLATION LEVEL READ COMMITTED
							END";

                $this->consulta = $this->connectDb->conect->prepare( $query );
                $this->consulta->execute();
                $this->connectDb->Desconectarse();
            }
        }catch( PDOException $e ){
			print_r($e);
            $this->set_error( $e );
            return false;
        }
        return true;
    }

    function set_error( $e ){
        $this->error = $e;
    }

    function get_error(){
        return $this->error;
    }

    function get_error_message(){
        return $this->error->getMessage();
    }
}
?>