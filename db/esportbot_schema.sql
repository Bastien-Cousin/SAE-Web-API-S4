--
-- PostgreSQL database dump
--

\restrict LheIzYKCnNxfezN4lCnkSBmIYFdV2veJD1SWQHRA8I5dPRaGtGwORYRn8q9zej3

-- Dumped from database version 15.16 (Debian 15.16-0+deb12u1)
-- Dumped by pg_dump version 15.16 (Debian 15.16-0+deb12u1)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: match_players; Type: TABLE; Schema: public; Owner: user
--

CREATE TABLE public.match_players (
    match_id bigint NOT NULL,
    player_id bigint NOT NULL
);


ALTER TABLE public.match_players OWNER TO "user";

--
-- Name: match_teams; Type: TABLE; Schema: public; Owner: user
--

CREATE TABLE public.match_teams (
    match_id bigint NOT NULL,
    team_id bigint NOT NULL
);


ALTER TABLE public.match_teams OWNER TO "user";

--
-- Name: matches; Type: TABLE; Schema: public; Owner: user
--

CREATE TABLE public.matches (
    pageid bigint NOT NULL,
    pagename text,
    namespace integer,
    objectname text,
    match2id text,
    match2bracketid text,
    status text,
    winner text,
    walkover text,
    resulttype text,
    finished boolean,
    mode text,
    type text,
    section text,
    game text,
    patch text,
    links jsonb,
    bestof integer,
    date timestamp without time zone,
    dateexact boolean,
    stream jsonb,
    vod text,
    tournament text,
    parent text,
    tickername text,
    shortname text,
    series text,
    icon text,
    iconurl text,
    icondark text,
    icondarkurl text,
    liquipediatier text,
    liquipediatiertype text,
    publishertier text,
    extradata jsonb,
    match2bracketdata jsonb,
    match2games jsonb,
    match2opponents jsonb,
    winner_team_id bigint
);


ALTER TABLE public.matches OWNER TO "user";

--
-- Name: players; Type: TABLE; Schema: public; Owner: user
--

CREATE TABLE public.players (
    pageid bigint NOT NULL,
    pagename text,
    namespace integer,
    objectname text,
    id text,
    alternateid text,
    name text,
    localizedname text,
    type text,
    nationality text,
    nationality2 text,
    nationality3 text,
    region text,
    birthdate date,
    deathdate date,
    teampagename text,
    teamtemplate text,
    links jsonb,
    status text,
    earnings integer,
    earningsbyyear jsonb,
    extradata jsonb,
    wiki text,
    team_id bigint
);


ALTER TABLE public.players OWNER TO "user";

--
-- Name: team_players; Type: TABLE; Schema: public; Owner: user
--

CREATE TABLE public.team_players (
    team_id bigint NOT NULL,
    player_id bigint NOT NULL
);


ALTER TABLE public.team_players OWNER TO "user";

--
-- Name: teams; Type: TABLE; Schema: public; Owner: user
--

CREATE TABLE public.teams (
    pageid bigint NOT NULL,
    pagename text,
    namespace integer,
    objectname text,
    name text,
    locations jsonb,
    region text,
    logo text,
    logourl text,
    logodark text,
    logodarkurl text,
    textlesslogourl text,
    textlesslogodarkurl text,
    status text,
    createdate date,
    disbanddate date,
    earnings bigint,
    earningsbyyear jsonb,
    template text,
    links jsonb,
    extradata jsonb,
    wiki text
);


ALTER TABLE public.teams OWNER TO "user";

--
-- Name: match_players match_players_pkey; Type: CONSTRAINT; Schema: public; Owner: user
--

ALTER TABLE ONLY public.match_players
    ADD CONSTRAINT match_players_pkey PRIMARY KEY (match_id, player_id);


--
-- Name: match_teams match_teams_pkey; Type: CONSTRAINT; Schema: public; Owner: user
--

ALTER TABLE ONLY public.match_teams
    ADD CONSTRAINT match_teams_pkey PRIMARY KEY (match_id, team_id);


--
-- Name: players players_objectname_key; Type: CONSTRAINT; Schema: public; Owner: user
--

ALTER TABLE ONLY public.players
    ADD CONSTRAINT players_objectname_key UNIQUE (objectname);


--
-- Name: players players_pkey; Type: CONSTRAINT; Schema: public; Owner: user
--

ALTER TABLE ONLY public.players
    ADD CONSTRAINT players_pkey PRIMARY KEY (pageid);


--
-- Name: team_players team_players_pkey; Type: CONSTRAINT; Schema: public; Owner: user
--

ALTER TABLE ONLY public.team_players
    ADD CONSTRAINT team_players_pkey PRIMARY KEY (team_id, player_id);


--
-- Name: teams teams_objectname_key; Type: CONSTRAINT; Schema: public; Owner: user
--

ALTER TABLE ONLY public.teams
    ADD CONSTRAINT teams_objectname_key UNIQUE (objectname);


--
-- Name: teams teams_pkey; Type: CONSTRAINT; Schema: public; Owner: user
--

ALTER TABLE ONLY public.teams
    ADD CONSTRAINT teams_pkey PRIMARY KEY (pageid);


--
-- Name: matches unique_objectname; Type: CONSTRAINT; Schema: public; Owner: user
--

ALTER TABLE ONLY public.matches
    ADD CONSTRAINT unique_objectname UNIQUE (objectname);


--
-- Name: matches unique_pageid; Type: CONSTRAINT; Schema: public; Owner: user
--

ALTER TABLE ONLY public.matches
    ADD CONSTRAINT unique_pageid UNIQUE (pageid);


--
-- Name: match_teams fk_match; Type: FK CONSTRAINT; Schema: public; Owner: user
--

ALTER TABLE ONLY public.match_teams
    ADD CONSTRAINT fk_match FOREIGN KEY (match_id) REFERENCES public.matches(pageid) ON DELETE CASCADE;


--
-- Name: match_players fk_match_mp; Type: FK CONSTRAINT; Schema: public; Owner: user
--

ALTER TABLE ONLY public.match_players
    ADD CONSTRAINT fk_match_mp FOREIGN KEY (match_id) REFERENCES public.matches(pageid) ON DELETE CASCADE;


--
-- Name: match_players fk_player_mp; Type: FK CONSTRAINT; Schema: public; Owner: user
--

ALTER TABLE ONLY public.match_players
    ADD CONSTRAINT fk_player_mp FOREIGN KEY (player_id) REFERENCES public.players(pageid) ON DELETE CASCADE;


--
-- Name: team_players fk_player_tp; Type: FK CONSTRAINT; Schema: public; Owner: user
--

ALTER TABLE ONLY public.team_players
    ADD CONSTRAINT fk_player_tp FOREIGN KEY (player_id) REFERENCES public.players(pageid) ON DELETE CASCADE;


--
-- Name: match_teams fk_team; Type: FK CONSTRAINT; Schema: public; Owner: user
--

ALTER TABLE ONLY public.match_teams
    ADD CONSTRAINT fk_team FOREIGN KEY (team_id) REFERENCES public.teams(pageid) ON DELETE CASCADE;


--
-- Name: players fk_team; Type: FK CONSTRAINT; Schema: public; Owner: user
--

ALTER TABLE ONLY public.players
    ADD CONSTRAINT fk_team FOREIGN KEY (team_id) REFERENCES public.teams(pageid);


--
-- Name: team_players fk_team_tp; Type: FK CONSTRAINT; Schema: public; Owner: user
--

ALTER TABLE ONLY public.team_players
    ADD CONSTRAINT fk_team_tp FOREIGN KEY (team_id) REFERENCES public.teams(pageid) ON DELETE CASCADE;


--
-- Name: matches fk_winner_team; Type: FK CONSTRAINT; Schema: public; Owner: user
--

ALTER TABLE ONLY public.matches
    ADD CONSTRAINT fk_winner_team FOREIGN KEY (winner_team_id) REFERENCES public.teams(pageid);


--
-- PostgreSQL database dump complete
--

\unrestrict LheIzYKCnNxfezN4lCnkSBmIYFdV2veJD1SWQHRA8I5dPRaGtGwORYRn8q9zej3

