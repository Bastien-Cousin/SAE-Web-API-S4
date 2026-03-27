-- CreateTable
CREATE TABLE "tournament" (
    "pageid" BIGINT NOT NULL,
    "pagename" TEXT,
    "namespace" INTEGER,
    "objectname" TEXT NOT NULL,
    "name" TEXT,
    "shortname" TEXT,
    "tickername" TEXT,
    "banner" TEXT,
    "bannerurl" TEXT,
    "bannerdark" TEXT,
    "bannerdarkurl" TEXT,
    "icon" TEXT,
    "iconurl" TEXT,
    "icondark" TEXT,
    "icondarkurl" TEXT,
    "seriespage" TEXT,
    "serieslist" JSONB,
    "previous" TEXT,
    "previous2" TEXT,
    "next" TEXT,
    "next2" TEXT,
    "game" TEXT,
    "mode" TEXT,
    "patch" TEXT,
    "endpatch" TEXT,
    "type" TEXT,
    "organizers" TEXT,
    "startdate" DATE,
    "enddate" DATE,
    "sortdate" DATE,
    "locations" JSONB,
    "prizepool" BIGINT,
    "participantsnumber" INTEGER,
    "liquipediatier" TEXT,
    "liquipediatiertype" TEXT,
    "publishertier" TEXT,
    "status" TEXT,
    "maps" TEXT,
    "format" TEXT,
    "sponsors" TEXT,
    "extradata" JSONB,
    "wiki" TEXT,

    CONSTRAINT "tournament_pkey" PRIMARY KEY ("pageid")
);

-- CreateTable
CREATE TABLE "user" (
    "id" SERIAL NOT NULL,
    "username" TEXT NOT NULL,
    "email" TEXT NOT NULL,
    "password" TEXT NOT NULL,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updatedAt" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "user_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "prediction" (
    "id" SERIAL NOT NULL,
    "user_id" INTEGER NOT NULL,
    "match_id" BIGINT NOT NULL,
    "predicted_winner" INTEGER NOT NULL,
    "confidence" INTEGER,
    "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "prediction_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "prediction_game" (
    "id" SERIAL NOT NULL,
    "prediction_id" INTEGER NOT NULL,
    "game_number" INTEGER NOT NULL,
    "map" TEXT,
    "team1_score" INTEGER,
    "team2_score" INTEGER,
    "predicted_winner" INTEGER,
    "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "prediction_game_pkey" PRIMARY KEY ("id")
);

-- CreateIndex
CREATE UNIQUE INDEX "tournament_objectname_key" ON "tournament"("objectname");

-- CreateIndex
CREATE UNIQUE INDEX "user_username_key" ON "user"("username");

-- CreateIndex
CREATE UNIQUE INDEX "user_email_key" ON "user"("email");

-- CreateIndex
CREATE INDEX "prediction_user_id_idx" ON "prediction"("user_id");

-- CreateIndex
CREATE INDEX "prediction_match_id_idx" ON "prediction"("match_id");

-- CreateIndex
CREATE UNIQUE INDEX "prediction_user_id_match_id_key" ON "prediction"("user_id", "match_id");

-- CreateIndex
CREATE UNIQUE INDEX "prediction_game_prediction_id_game_number_key" ON "prediction_game"("prediction_id", "game_number");

-- AddForeignKey
ALTER TABLE "prediction" ADD CONSTRAINT "prediction_user_id_fkey" FOREIGN KEY ("user_id") REFERENCES "user"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "prediction" ADD CONSTRAINT "prediction_match_id_fkey" FOREIGN KEY ("match_id") REFERENCES "match"("pageid") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "prediction_game" ADD CONSTRAINT "prediction_game_prediction_id_fkey" FOREIGN KEY ("prediction_id") REFERENCES "prediction"("id") ON DELETE CASCADE ON UPDATE CASCADE;
