<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_CONNECTION') == 'pgsql') {
            DB::unprepared(<<<'sql'

            create or replace function min(uuid, uuid)
                returns uuid
                immutable parallel safe
                language plpgsql as
            $$
            begin
                return least($1, $2);
            end
            $$;

            create or replace aggregate min(uuid) (
                sfunc = min,
                stype = uuid,
                combinefunc = min,
                parallel = safe,
                sortop = operator (<)
                );

            create or replace function max(uuid, uuid)
                returns uuid
                immutable parallel safe
                language plpgsql as
            $$
            begin
                return greatest($1, $2);
            end
            $$;

            create or replace aggregate max(uuid) (
                sfunc = max,
                stype = uuid,
                combinefunc = max,
                parallel = safe,
                sortop = operator (>)
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
        if (env('DB_CONNECTION') == 'pgsql') {
            DB::unprepared(<<<'sql'

            drop aggregate if exists max(uuid);
            drop aggregate if exists min(uuid);
            drop function if exists max(uuid, uuid);
            drop function if exists min(uuid, uuid);
            sql);
        }
    }
};
