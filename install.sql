CREATE TABLE IF NOT EXISTS public.passaggi_stato (
	id_ordine integer,
	ora time without time zone,
	stato integer,
	PRIMARY KEY (id_ordine, stato),
	FOREIGN KEY (id_ordine) REFERENCES ordini(id) ON DELETE cascade ON UPDATE cascade
);
ALTER TABLE IF EXISTS public.passaggi_stato OWNER TO postgres;

CREATE TABLE IF NOT EXISTS public.modifiche (
	id_ordine integer,
	ora time without time zone,
	agente character varying(255) COLLATE pg_catalog."default",
	differenza numeric(10,2),
	righeModificate integer,
	cassaVecchia character varying(255) COLLATE pg_catalog."default",
	cassaNuova character varying(255) COLLATE pg_catalog."default",
	PRIMARY KEY (id_ordine, ora),
	FOREIGN KEY (id_ordine) REFERENCES ordini(id) ON DELETE cascade ON UPDATE cascade
);
ALTER TABLE IF EXISTS public.modifiche OWNER TO postgres;

CREATE TABLE IF NOT EXISTS public.dati_ingredienti (
	id_ingrediente integer,
	divisore integer DEFAULT 1,
	settore character varying(255),
	monitora boolean DEFAULT true,
	PRIMARY KEY (id_ingrediente),
	FOREIGN KEY (id_ingrediente) REFERENCES ingredienti(id) ON DELETE cascade ON UPDATE cascade
);
ALTER TABLE IF EXISTS public.dati_ingredienti OWNER TO postgres;

CREATE TABLE IF NOT EXISTS public.shiftstart (
	datetimestart timestamp without time zone
)
WITH ( OIDS = FALSE )
TABLESPACE pg_default;
ALTER TABLE IF EXISTS public.shiftstart OWNER to postgres;

CREATE FUNCTION public.pranzo_cena("time" time without time zone) RETURNS text
    LANGUAGE plpgsql
    AS $$
begin
if (time < '17:00:00.000') then
    return 'pranzo';
else
    return 'cena';
end if;
end;
$$;
ALTER FUNCTION public.pranzo_cena("time" time without time zone) OWNER TO postgres;

CREATE FUNCTION public.fixsequences() RETURNS boolean
    LANGUAGE plpgsql
    AS $$DECLARE
i TEXT;
BEGIN
  FOR i IN (SELECT tbls.table_name FROM information_schema.tables AS tbls INNER JOIN information_schema.columns AS cols ON tbls.table_name = cols.table_name WHERE tbls.table_catalog='sagra' AND tbls.table_schema='public' AND cols.column_name='id') LOOP
      EXECUTE 'SELECT setval(''"' || i || '_id_seq"'', (SELECT COALESCE(MAX(id),1) FROM ' || quote_ident(i) || '));';
  END LOOP;
  RETURN TRUE;
END;$$;
ALTER FUNCTION public.fixsequences() OWNER TO postgres;