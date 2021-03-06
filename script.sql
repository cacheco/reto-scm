USE [gestionconf]
GO
/****** Object:  Table [dbo].[huellas]    Script Date: 11/30/2019 9:20:16 AM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[huellas](
	[idRelease] [int] NULL,
	[nombreArtefacto] [varchar](500) NULL,
	[huella] [varchar](500) NULL,
	[tipoparametro] [smallint] NULL
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[release]    Script Date: 11/30/2019 9:20:17 AM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[release](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[nombre] [varchar](200) NULL,
 CONSTRAINT [PK_release] PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[tipoparametros]    Script Date: 11/30/2019 9:20:17 AM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[tipoparametros](
	[id] [smallint] IDENTITY(1,1) NOT NULL,
	[nombre] [varchar](50) NULL,
 CONSTRAINT [PK_tipoparametros] PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
ALTER TABLE [dbo].[huellas] ADD  CONSTRAINT [DF_huellas_tipoparametro]  DEFAULT ((1)) FOR [tipoparametro]
GO
ALTER TABLE [dbo].[huellas]  WITH CHECK ADD  CONSTRAINT [FK_huellas_release] FOREIGN KEY([idRelease])
REFERENCES [dbo].[release] ([id])
GO
ALTER TABLE [dbo].[huellas] CHECK CONSTRAINT [FK_huellas_release]
GO
