<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new Class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(env('DB_CONNECTION') == 'pgsql') {
            DB::unprepared(<<<sql

                CREATE OR REPLACE FUNCTION max (uuid, uuid)
                RETURNS uuid AS $$
                BEGIN
                    IF $1 IS NULL OR $1 < $2 THEN
                        RETURN $2;
                    END IF;

                    RETURN $1;
                END;
                $$ LANGUAGE plpgsql;


                CREATE OR REPLACE AGGREGATE max (uuid) (
                    sfunc = max,
                    stype = uuid
                );

                CREATE OR REPLACE FUNCTION min (uuid, uuid)
                RETURNS uuid AS $$
                BEGIN
                    IF $1 IS NULL OR $1 > $2 THEN
                        RETURN $2;
                    END IF;

                    RETURN $1;
                END;
                $$ LANGUAGE plpgsql;


                CREATE OR REPLACE AGGREGATE min (uuid) (
                    sfunc = min,
                    stype = uuid
                );
            sql);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if(env('DB_CONNECTION') == 'pgsql') {
            DB::unprepared(<<<sql

                DROP AGGREGATE IF EXISTS max(uuid);
                DROP AGGREGATE IF EXISTS min(uuid);
                DROP FUNCTION IF EXISTS max(uuid, uuid);
                DROP FUNCTION IF EXISTS min(uuid, uuid);
            sql);
        }
    }
};
