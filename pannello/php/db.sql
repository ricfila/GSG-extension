CREATE TABLE public.passaggi_stato (
	id_ordine integer,
	ora time without time zone,
	stato integer,
	PRIMARY KEY (id_ordine, stato),
	FOREIGN KEY (id_ordine) REFERENCES ordini(id) ON DELETE cascade ON UPDATE cascade
);

CREATE TABLE public.modifiche (
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
