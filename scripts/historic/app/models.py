from django.db import models
from django.db.models import Q

class Person(models.Model):
	import_id = models.IntegerField(null=True)
	created_at = models.DateTimeField(null=True)
	updated_at = models.DateTimeField(null=True)
	slug = models.CharField(max_length=255, null=True)
	honorific = models.CharField(max_length=255, null=True)
	firstname = models.CharField(max_length=255, null=True)
	lastname = models.CharField(max_length=255, null=True)
	full_firstnames = models.CharField(max_length=255, null=True)
	date_of_birth = models.DateField(null=True)
	date_of_death = models.DateField(null=True)
	estimated_date_of_birth = models.NullBooleanField(null=True)
	estimated_date_of_death = models.NullBooleanField(null=True)
	data_source_id = models.IntegerField(null=True)
	contribution_count = models.IntegerField(null=True)
	membership_count = models.IntegerField(null=True)

	class Meta:
		db_table = 'people'

	def __unicode__(self):
		return u'%s %s %s' % (self.honorific, self.firstname, self.lastname)

class Election(models.Model):
	import_id = models.IntegerField(null=True)
	date = models.DateField(null=True)
	dissolution_date = models.DateField(null=True)
	created_at = models.DateTimeField(null=True)
	updated_at = models.DateTimeField(null=True)

	class Meta:
		db_table = 'elections'
		ordering = ('date',)

	def __unicode__(self):
		return u'%s (dissolution %s)' % (self.date, self.dissolution_date)

class ConstituencyManager(models.Manager):
	def in_year(self, year):
		return self.filter(Q(start_year__lte=year, end_year__gte=year) | Q(start_year__lte=year, end_year__isnull=True))

class Constituency(models.Model):
	name = models.CharField(max_length=255, null=True)
	slug = models.CharField(max_length=255, null=True)
	import_id = models.IntegerField(null=True)
	start_year = models.IntegerField(null=True)
	end_year = models.IntegerField(null=True)
	area_type = models.CharField(max_length=255, null=True)
	region = models.CharField(max_length=255, null=True)
	data_source_id = models.IntegerField(null=True)
	objects = ConstituencyManager()

	class Meta:
		db_table = 'constituencies'
		ordering = ('start_year', 'end_year')

	def __unicode__(self):
		return u'%s (%s-%s)' % (self.full_name(), self.start_year, self.end_year)

	def full_name(self):
		if self.area_type == 'county':
			return 'County %s' % self.name
		elif self.area_type == 'borough':
			return '%s Borough' % self.name
		elif self.name in ('Wellington', 'Newport', 'Richmond'):
			return '%s (%s)' % (self.name, self.region)
		return self.name

class ConstituencyAlias(models.Model):
	constituency = models.ForeignKey(Constituency, null=True)
	alias = models.CharField(max_length=255, null=True)
	start_date = models.DateField(null=True)
	end_date = models.DateField(null=True)
	created_at = models.DateTimeField(null=True)
	updated_at = models.DateTimeField(null=True)

	class Meta:
		db_table = 'constituency_aliases'

	def __unicode__(self):
		return '%s (for %s)' % (self.alias, self.constituency)

class CommonsMembership(models.Model):
	import_id = models.IntegerField(null=True)
	person = models.ForeignKey(Person, null=True)
	constituency = models.ForeignKey(Constituency, null=True)
	start_date = models.DateField(null=True)
	end_date = models.DateField(null=True)
	created_at = models.DateTimeField(null=True)
	updated_at = models.DateTimeField(null=True)
	estimated_start_date = models.NullBooleanField(null=True)
	estimated_end_date = models.NullBooleanField(null=True)
	data_source_id = models.IntegerField(null=True)

	class Meta:
		db_table = 'commons_memberships'

	def __unicode__(self):
		return u'%s in %s, %s - %s' % (self.person, self.constituency, self.start_date, self.end_date)

class Party(models.Model):
	name = models.CharField(max_length=255, null=True, unique=True)

	class Meta:
		db_table = 'parties'
		ordering = ('name',)

	def __unicode__(self):
		return self.name

class Section(models.Model):
	type = models.CharField(max_length=255, null=True)
	title = models.TextField(null=True)
	start_column = models.CharField(max_length=255, null=True)
	sitting_id = models.IntegerField(null=True)
	parent_section_id = models.ForeignKey('self')
	slug = models.CharField(max_length=255, null=True)
	end_column = models.CharField(max_length=255, null=True)
	date = models.DateField(null=True)

	class Meta:
		db_table = 'sections'

	def __unicode__(self):
		return self.title

class Contribution(models.Model):
	type = models.CharField(max_length=255, null=True)
	xml_id = models.CharField(max_length=255, null=True)
	member_name = models.CharField(max_length=255, null=True)
	member_suffix = models.CharField(max_length=255, null=True)
	text = models.TextField(null=True)
	column_range = models.CharField(max_length=255, null=True)
	question_no = models.CharField(max_length=255, null=True)
	procedural_note = models.CharField(max_length=255, null=True)
	section = models.ForeignKey(Section, null=True)
	style = models.CharField(max_length=255, null=True)
	time = models.TimeField(null=True)
	member = models.ForeignKey(Person, null=True)
	constituency_name = models.CharField(max_length=255, null=True)
	constituency = models.ForeignKey(Constituency, null=True)
	party_name = models.CharField(max_length=255, null=True)
	party_id = models.CharField(max_length=255, null=True)
	commons_membership = models.ForeignKey(CommonsMembership, null=True)
	prefix = models.CharField(max_length=255, null=True)
	anchor_id = models.CharField(max_length=255, null=True)
	lords_membership_id = models.IntegerField(null=True)
	start_image = models.CharField(max_length=255, null=True)
	end_image = models.CharField(max_length=255, null=True)

	class Meta:
		db_table = 'contributions'

	def __unicode__(self):
		return u'%s by %s (%d)' % (self.type, self.member_name, self.id)

#| alternative_names             |

